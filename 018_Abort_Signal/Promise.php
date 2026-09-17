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

  function await(?AbortSignal $signal = null): mixed
  {
    // 1. Проверяем, не отменено ли всё еще до начала ожидания
    if ($signal?->isAborted()) throw new AbortException($signal->getReason());

    // Если промис уже выполнен, сразу возвращаем значение (без паузы)
    if ($this->state === State::FULFILLED) return $this->value;
    if ($this->state === State::REJECTED) throw $this->value instanceof \Throwable ?$this->value:new \RuntimeException((string)$this->value);

    // Проверяем, находимся ли мы внутри файбера
    $currentFiber = \Fiber::getCurrent();
    if($currentFiber === null) //TODO: It's possible
      throw new \LogicException("Метод await() можно вызывать только внутри Fiber.");

    // 2. Если передан сигнал, подписываемся на него
    if ($signal)
      $signal->onAbort(function(string $reason) use ($currentFiber) {
        // Если файбер все еще на паузе, бросаем в него исключение отмены
        if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
          $currentFiber->throw(new AbortException($reason));
      });

    // Подписываемся на этот же промис
    $this->then(
      // Когда выполнится - возобновляем файбер и передаем результат
      fn($value) => !$currentFiber->isTerminated() && $currentFiber->resume($value),
      // Если ошибка - возобновляем файбер, бросая в него исключение
      fn($exception) => !$currentFiber->isTerminated() && $currentFiber->throw(
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

  /**
   * Ограничивает время выполнения текущего промиса.
   * Если промис не успевает выполниться за $seconds секунд, возвращается отклонённый промис.
   */
  function timeout(float $seconds, ?string $exceptionMessage = null): self
  {
    // Создаем промис-таймер, который гарантированно упадет через указанное время.
    // Для создания таймера используем наш Loop::delay.
    $timeoutDeferred = new Deferred();
    
    Loop::delay($seconds, function() use ($timeoutDeferred, $exceptionMessage, $seconds) {
      $message = $exceptionMessage ?? "The operation exceeded the timeout limit of {$seconds} seconds.";
      $timeoutDeferred->reject(new \RuntimeException($message));
    });

    // Запускаем гонку между текущим промисом ($this) и промисом-таймером.
    // Возвращаем результат этой гонки.
    return self::race([$this, $timeoutDeferred->promise()]);
  }

  /**
   * Ожидает завершения всех промисов (успешного или с ошибкой).
   * Возвращает массив структур: 
   * ['status' => 'fulfilled', 'value' => $value] или ['status' => 'rejected', 'reason' => $reason]
   */
  static function allSettled(array $promises): self
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
      if (!$promise instanceof self)
      {
        // Если передали обычное значение - это моментальный успех
        $promise = (new Deferred())->resolve($promise);
      }

      // Нам важен сам факт завершения, поэтому логика сбора идентична для обоих колбэков
      $promise->then(
        function ($value) use (&$results, &$remaining, $key, $deferred)
        {
          $results[$key] = [
            'status' => 'fulfilled',
            'value'  => $value
          ];
          $remaining--;
          if ($remaining === 0)
            $deferred->resolve($results);
        },
        function ($reason) use (&$results, &$remaining, $key, $deferred)
        {
          $results[$key] = [
            'status' => 'rejected',
            'reason' => $reason
          ];
          $remaining--;
          if ($remaining === 0)
            $deferred->resolve($results);
        }
      );
    }

    return $deferred->promise();
  }
}
