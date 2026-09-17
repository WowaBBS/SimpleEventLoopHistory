<?
//Promise::all([...]) - ждет выполнения всех промисов. Если хоть один упал - падает вся цепочка.
//Promise::race([...]) - возвращает результат первого завершившегося промиса.
//Promise::resolve($value) / Promise::reject($reason) - для быстрого создания уже выполненных или отклоненных промисов.

class Promise
{
  private State $state = State::PENDING;
  private mixed $value = null;
  private array $handlers = [];
  
  private function Handler($nextDeferred, $onFulfilled, $onRejected)
  {
    try
    {
      if($this->state === State::FULFILLED)
      {
        if($onFulfilled)
        {
          $result = $onFulfilled($this->value);
          // Если вернулся другой промис, ждем его
          if($result instanceof self)
          {
            $result->then(
              fn($val) => $nextDeferred->resolve($val),
              fn($err) => $nextDeferred->reject($err)
            );
          }
          else
            $nextDeferred->resolve($result);
        }
        else
          $nextDeferred->resolve($this->value);
      }
      else if($this->state === State::REJECTED)
      {
        if($onRejected)
        {
          $result = $onRejected($this->value);
          $nextDeferred->resolve($result);
        }
        else
        {
          $nextDeferred->reject($this->value);
        }
      }
    }
    catch (Throwable $e)
    {
      $nextDeferred->reject($e);
    }
  }

  function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
  {
    $nextDeferred = new Deferred();

    $this->handlers[] = fn()=>$this->Handler($nextDeferred, $onFulfilled, $onRejected);
    //Function () Use ($nextDeferred, $onFulfilled, $onRejected) { $this->Handler($nextDeferred, $onFulfilled, $onRejected); };

    if($this->state !== State::PENDING)
      $this->executeHandlers();

    return $nextDeferred->promise();
  }

  function catch(callable $onRejected): self
  {
    return $this->then(null, $onRejected);
  }

  function Resolve(mixed $value): void
  {
    if($this->state!==State::PENDING) return;
    $this->state = State::FULFILLED;
    $this->value = $value;
    $this->executeHandlers();
  }

  function Reject(mixed $reason): void
  {
    if($this->state !== State::PENDING) return;
    $this->state = State::REJECTED;
    $this->value = $reason;
    $this->executeHandlers();
  }

  function ExecuteHandlers(): void
  {
    foreach($this->handlers as $handler)
      $handler();
    
    $this->handlers = [];
  }

  // ... (предыдущие свойства: state, value, handlers)
  function await(): mixed
  {
    // Если промис уже выполнен, сразу возвращаем значение (без паузы)
    if ($this->state === State::FULFILLED) return $this->value;
    if ($this->state === State::REJECTED)
      throw $this->value instanceof \Throwable 
        ?$this->value
        :new \RuntimeException((string)$this->value);

    // Проверяем, находимся ли мы внутри файбера
    $currentFiber = \Fiber::getCurrent();
    if($currentFiber === null) //TODO: It's possible
      throw new \LogicException("Метод await() можно вызывать только внутри Fiber.");

    // Подписываемся на этот же промис
    $this->then(
      // Когда выполнится - возобновляем файбер и передаем результат
      fn($value) => $currentFiber->resume($value),
      // Если ошибка - возобновляем файбер, бросая в него исключение
      fn($exception) => $currentFiber->throw(
        $exception instanceof \Throwable
          ?$exception
          :new \RuntimeException((string)$exception)
      )
    );

    // Приостанавливаем файбер. Управление возвращается в основной поток (Event Loop)
    // Когда вызовется resume() или throw(), код продолжится со следующей строки
    return \Fiber::suspend();
  }

  /**
   * Ожидает выполнения всех промисов в массиве.
   * Возвращает массив результатов в тех же ключах.
   */
  static function all(array $promises): self
  {
    $deferred = new Deferred();
    if (empty($promises))
    {
      $deferred->resolve([]);
      return $deferred->promise();
    }

    $results = [];
    $remaining = count($promises);

    foreach ($promises as $key => $promise)
    {
      // Если передали обычное значение вместо промиса - оборачиваем его
      if (!$promise instanceof self)
        $promise = (new Deferred())->resolve($promise);

      $promise->then(
        function ($value) use (&$results, &$remaining, $key, $deferred)
        {
          $results[$key] = $value;
          $remaining--;
          
          if ($remaining === 0)
            $deferred->resolve($results);
        },
        function ($reason) use ($deferred)
        {
          // Если упал хотя бы один - реджектим всю пачку (стандартное поведение Promise.all)
          $deferred->reject($reason);
        }
      );
    }

    return $deferred->promise();
  }
  
  /**
   * Возвращает промис, который завершится так же и с тем же результатом,
   * как и первый завершившийся промис из массива (успех или ошибка).
   */
  static function race(array $promises): self
  {
    $deferred = new Deferred();

    if (empty($promises))
    {
      // По спецификации пустой race() навечно остается в состоянии PENDING.
      // Но для удобства можно либо выбросить исключение, либо оставить так.
      return $deferred->promise();
    }

    foreach ($promises as $promise)
    {
      if (!$promise instanceof self)
        $promise = (new Deferred())->resolve($promise);

      // Кто первый вызвал метод у deferred, тот и зафиксировал состояние.
      // Повторные вызовы resolve/reject внутри Deferred просто проигнорируются.
      $promise->then(
        fn($value) => $deferred->resolve($value),
        fn($reason) => $deferred->reject($reason)
      );
    }

    return $deferred->promise();
  }

  /**
   * Возвращает промис, который выполнится успешно, как только выполнится успешно
   * хотя бы один из промисов в массиве.
   * Если все промисы отклонены, возвращает ошибку AggregateException.
   */
  static function any(array $promises): self
  {
    $deferred = new Deferred();

    if (empty($promises))
    {
      $deferred->reject(new \RuntimeException("Promise::any() передан пустой массив."));
      return $deferred->promise();
    }

    $errors = [];
    $remaining = count($promises);

    foreach ($promises as $key => $promise)
    {
      if (!$promise instanceof self)
      {
        // Обычное значение считается мгновенным успехом
        $deferred->resolve($promise);
        return $deferred->promise();
      }

      $promise->then(
        function ($value) use ($deferred)
        {
          // Первый же успех резолвит общий промис
          $deferred->resolve($value);
        },
        function ($reason) use (&$errors, &$remaining, $key, $deferred)
        {
          $errors[$key] = $reason;
          $remaining--;

          // Если упали ВСЕ промисы, отклоняем общий промис
          if ($remaining === 0)
            $deferred->reject(new AggregateException("Все промисы были отклонены", $errors));
        }
      );
    }

    return $deferred->promise();
  }
}

/**
 * Кастомное исключение для метода Promise::any(),
 * хранящее ошибки всех упавших промисов.
 */
class AggregateException extends \RuntimeException
{
  private array $errors;

  function __construct(string $message, array $errors = [])
  {
    parent::__construct($message);
    $this->errors = $errors;
  }

  function getErrors(): array { return $this->errors; }
}