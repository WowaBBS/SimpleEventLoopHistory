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
}
