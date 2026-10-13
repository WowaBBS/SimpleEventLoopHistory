<?
class Pipeline 
{
  private \Generator $generator;
  private ?Pipeline $parent = null;

  // Количество дочерних пайплайнов, которые читают данные из этого
  private int $childCount = 0;

  // Был ли этот конкретный шаг отменен
  private bool $isCancelled = false;

  // Защищено ли это звено от принудительной отмены (например, для записи критических логов/БД)
  private bool $isProtected = false;

  function __construct(\Generator $generator, ?Pipeline $parent = null)
  {
    $this->generator = $generator;
    $this->parent = $parent;

    if ($this->parent)
      $this->parent->childCount++; // Увеличиваем счетчик ссылок у родителя
  }

  /**
   * Создает конвейер из любого итерируемого источника (генератора)
   */
  static function from(\Generator $generator): self { return new self($generator); }

  /**
   * Делает текущее звено защищенным от отмены.
   * Всё, что выше него, не прервется, пока данные не пройдут через этот шаг.
   */
  function uncancelable(): self
  {
    $this->isProtected = true;
    return $this;
  }

  /**
   * Внутренний метод для создания следующего шага
   */
  private function next(\Generator $nextGenerator): self { return new self($nextGenerator, $this); }

  /**
   * Метод инициации отмены снизу вверх
   */
  function abortPipeline(?string $reason = null): void
  {
    // Если звено защищено, мы блокируем отмену вверх!
    if ($this->isProtected) return;
    if ($this->isCancelled) return;

    $this->isCancelled = true;

    // Если есть родитель, уведомляем его, что мы отписались
    if ($this->parent)
    {
      $this->parent->childCount--;

      // КРИТИЧЕСКОЕ ПРАВИЛО: Родитель отменяется только если у него
      // НЕ ОСТАЛОСЬ других активных дочерних потребителей!
      if ($this->parent->childCount <= 0)
        $this->parent->abortPipeline($reason);
    }
  }

  public function isCancelled(): bool { return $this->isCancelled; }

  // --- ОПЕРАТОРЫ КОНВЕЙЕРА ---

  /**
   * Преобразует каждый элемент потока.
   * Если колбэк возвращает Promise, дожидается его выполнения.
   */
  function map(callable $callback): self
  {
    $mapper = function() use ($callback): \Generator {
      while ($this->generator->valid())
      {
        if ($this->isCancelled) return;

        $item = $this->generator->current();
        $this->generator->next();

        // Если генератор опустел на этом шаге
        $isEnd = !$this->generator->valid();
        $result = $callback($item, $isEnd);

        // Если внутри колбэка вернули массив с промисами, резолвим их
        if (is_array($result))
          $result = $this->resolveArrayWithPromises($result);
        elseif ($result instanceof Promise)
          $result = $result->await();

        yield $result;
      }
    };

    return $this->next($mapper());
  }

  /**
   * Группирует элементы по количеству или таймауту в 1 секунду
   */
  function group(int $count, float $timeout): self
  {
    $grouper = function() use ($count, $timeout): \Generator {
      $chunk = [];
      $deadline = microtime(true) + $timeout;

      while (true)
      {
        if ($this->isCancelled) return;

        if ($this->generator->valid())
        {
          $chunk[] = $this->generator->current();
          $this->generator->next();
        }
        else
        {
          if (!empty($chunk)) yield $chunk;
          
          break;
        }

        $now = microtime(true);
        if (count($chunk) >= $count || $now >= $deadline)
        {
          yield $chunk;
          $chunk = [];
          $deadline = microtime(true) + $timeout;
        }

        // Даем другим файберам подышать
        sleepAsync(0.001)->await();
      }
    };

    return $this->next($grouper());
  }

  /**
   * Подсчитывает сумму полей и выдает ОДНО финальное значение в конце
   */
  function sum(callable $selector): self
  {
    $summer = function() use ($selector): \Generator {
      $total = 0;
      foreach ($this->generator as $item)
      {
        if ($this->isCancelled) return;

        // Если элемент - это массив строк (например, из TableB)
        if (is_array($item))
        {
          foreach ($item as $subItem) 
            $total += $selector($subItem);
        }
        else
          $total += $selector($item);
      }
      yield $total;
    };
    return $this->next($summer());
  }

  // --- ТЕРМИНАЛЬНЫЕ МЕТОДЫ ---

  /**
   * Терминальный метод. Запускает весь конвейер и ждет окончания
   */
  function finish(): void
  {
    foreach ($this->generator as $item)
    {
      // Просто прокручиваем генератор до конца, активируя всю цепочку
    }
  }

  /**
   * Терминальный метод. Запускает конвейер и возвращает Promise,
   * который выполнится по завершении и вернет финальное значение потока.
   * Принимает опциональный сигнал отмены.
   */
  function collect(): Promise
  {
    $deferred = new Deferred();
    $promise = $deferred->promise();

    // Если финальный промис отменяют, запускаем цепочку умной отмены вверх
    $promise->setOnCancel(function(AbortException $exception) {
      $this->abortPipeline($exception->getMessage());
    });

    // Запускаем асинхронную раскрутку конвейера
    Loop::async(function() use ($deferred, $promise) {
      try
      {
        $lastValue = null;

        while ($this->generator->valid())
        {
          // Если промис был отменен (прямо или через сигнал),
          // останавливаем цикл. Deferred уже переведен в REJECTED внутри Promise::abort()
          if ($this->isCancelled) return; // Выходим из файбера, deferred уже отклонен в колбэке onAbort

          $lastValue = $this->generator->current();
          $this->generator->next();

          try
          {
            // Даем Event Loop переключать контекст на другие задачи
            sleepAsync(0)->await();
          }
          catch (AbortException $e)
          {
            return;
          }

          if ($this->isCancelled) return;
        }

       // Если дошли до конца и промис все еще ожидает (не отменен)
        if (!$this->isCancelled)
          $deferred->resolve($lastValue); // резолвим финальный результат
      }
      catch (\Throwable $e)
      {
        // Любая ошибка в цепочке отклонит итоговый промис
        if (!$this->isCancelled) 
          $deferred->reject($e);
      }
    });

    return $promise;
  }

  /**
   * Терминальный метод для простого запуска.
   * Запускает конвейер <вхолостую> ради выполнения побочных эффектов.
   * Ничего не возвращает (void).
   */
  function run(): void { $this->collect(); }

  /**
   * Вспомогательный метод для глубокого разрешения промисов в массиве
   */
  private function resolveArrayWithPromises(array $array): array 
  {
    $promises =[];
    foreach ($array as $key => $value)
      if ($value instanceof Promise)
      {
        // Если весь пайплайн отменяется, гасим внутренние промисы
        $promises[$key] = $value;
        $this->signalInternalPromiseCancellation($value);
      }

    if (!empty($promises))
    {
      $settled = Promise::allSettled($promises)->await();
      foreach ($settled as $key => $res)
      {
        // Заменяем промис на его реальный результат (или объект ответа)
        $array[$key] = $res['status'] === 'fulfilled' ? $res['value'] : $res['reason'];
      }
    }
    return $array;
  }

  private function signalInternalPromiseCancellation(Promise $promise): void
  {
    // Кастомный хелпер: если пайплайн отменяется, мы пинаем промис
    Loop::async(function() use ($promise) {
      while (true)
      {
        if ($this->isCancelled)
        {
          $promise->abort("Пайплайн отменен.");
          break;
        }
        if ($promise->await() !== null) // Если промис выполнился сам, выходим
          break;
        sleepAsync(0.01)->await();
      }
    });
  }
}