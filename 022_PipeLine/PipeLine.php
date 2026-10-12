<?
class Pipeline 
{
  private \Generator $generator;

  function __construct(\Generator $generator)
  {
    $this->generator = $generator;
  }

  /**
   * Создает конвейер из любого итерируемого источника (генератора)
   */
  static function from(\Generator $generator): self
  {
    return new self($generator);
  }

  /**
   * Преобразует каждый элемент потока.
   * Если колбэк возвращает Promise, дожидается его выполнения.
   */
  function map(callable $callback): self
  {
    $mapper = function() use ($callback): \Generator {
      $isEnd = false;
      while ($this->generator->valid())
      {
        $item = $this->generator->current();
        $this->generator->next();

        // Если генератор опустел на этом шаге
        if (!$this->generator->valid())
          $isEnd = true;

        $result = $callback($item, $isEnd);

        // Если внутри колбэка вернули массив с промисами, резолвим их
        if (is_array($result))
          $result = $this->resolveArrayWithPromises($result);
        elseif ($result instanceof Promise)
          $result = $result->await();

        yield $result;
      }
    };

    return new self($mapper());
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
        if ($this->generator->valid())
        {
          $chunk[] = $this->generator->current();
          $this->generator->next();
        }
        else
        {
          if (!empty($chunk))
            yield $chunk;
          
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

    return new self($grouper());
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

    return new self($summer());
  }

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
   * Вспомогательный метод для глубокого разрешения промисов в массиве
   */
  private function resolveArrayWithPromises(array $array): array
  {
    $promises = [];
    foreach ($array as $key => $value) 
      if ($value instanceof Promise) 
        $promises[$key] = $value;

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
}