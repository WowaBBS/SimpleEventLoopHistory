<?
//TODO: try(func), finally(callback), construct(callback(resolve, reject))
//   enable, Unhandled Rejections, Promise.denodeify(fn), Promise.nodeify(fn)


//Promise::all([...]) - ждет выполнения всех промисов. Если хоть один упал - падает вся цепочка.
//Promise::race([...]) - возвращает результат первого завершившегося промиса.
//Promise::resolve($value) / Promise::reject($reason) - для быстрого создания уже выполненных или отклоненных промисов.

class Promise
{
  private State $state = State::PENDING;
  private mixed $value = null;
  private array $handlers = [];
  // Колбэк, который вызывается при отмене для очистки ресурсов (стримов, таймеров)
  private ?\Closure $onCancel = null; 
  
  function isPending   () { return $this->state === State::PENDING   ; }
  function isFulfilled () { return $this->state === State::FULFILLED ; }
  function isRejected  () { return $this->state === State::REJECTED  ; }

  // Метод для установки колбэка очистки (используется внутри AsyncFile/httpGet)
  function setOnCancel(callable $callback): self
  {
    if ($this->onCancel) Logger::error('onCancel overwriting')->backTrace();
    $this->onCancel = \Closure::fromCallable($callback);
    return $this;
  }

  /**
   * Привязывает этот промис к указанному сигналу отмены.
   * Возвращает текущий промис для чейнинга (Fluent API).
   */
  public function abortBy(AbortSignal $signal): self { $signal->follow($this); return $this; }
  
  /**
   * Принудительная отмена конкретного промиса
   */
  function abort(?string $reason = null): void
  {
    if ($this->state !== State::PENDING) return;

    $reason = $reason ?? 'Promise aborted.';
    $exception = new AbortException($reason);

    // 1. Вызываем очистку системных ресурсов, если она была задана
    if ($this->onCancel)
      ($this->onCancel)($exception);

    // 2. Отклоняем промис с ошибкой AbortException
    $this->reject($exception);
  }

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
  
  static function keyHandler(object|int|string $key): int|string { return is_object($key)? spl_object_id($key):$key; }
  
  function addHandler(callable $handler, null|object|int|string $key = null)
  {
    $this->handlers[self::keyHandler($key?? $handler)] = $handler;
  }

  function removeHandler(object|int|string $key)
  {
    unset($this->handlers[self::keyHandler($key)]);
  }
  
  function hasHandler(object|int|string $key)
  {
    return array_key_exists(self::keyHandler($key), $this->handlers);
  }
  
  function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
  {
    $nextDeferred = new Deferred();

    $this->addHandler(fn($promise)=>$promise->Handler($nextDeferred, $onFulfilled, $onRejected), $nextDeferred->promise());

    if($this->state !== State::PENDING)
      $this->executeHandlers();

    return $nextDeferred->promise();
  }

  function catch(callable $onRejected): self
  {
    return $this->then(null, $onRejected);
  }

  function resolve(mixed $value=null): void
  {
    if($this->state!==State::PENDING) return;
    $this->state = State::FULFILLED;
    $this->value = $value;
    $this->executeHandlers();
  }

  function reject(mixed $reason): void
  {
    if($this->state !== State::PENDING) return;
    $this->state = State::REJECTED;
    $this->value = $reason;
    $this->executeHandlers();
  }

  function executeHandlers(): void
  {
    $handlers=$this->handlers;
    if (!$handlers) return;
    
    $this->handlers = [];
    foreach($handlers as $handler)
      $handler($this);
    
    if ($this->handlers) Logger::error('Unexpected behavior, added handlers during executiong')->backtrace();
  }
  
  function getThrowable():?\Throwable { return $this->isRejected()? ($this->value instanceof \Throwable ?$this->value:new \RuntimeException((string)$this->value)):Null; }

  function await(): mixed
  {
    // Если промис уже выполнен, сразу возвращаем значение (без паузы)
    if ($this->state === State::FULFILLED) return $this->value;
    if ($this->state === State::REJECTED) throw $this->getThrowable();

    // Проверяем, находимся ли мы внутри файбера
    $currentFiber = \Fiber::getCurrent();
    if($currentFiber === null) //TODO: It's possible
      throw new \LogicException("The await() method can only be called within a Fiber.");

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
      $deferred->reject(new \RuntimeException("An empty array was passed to Promise::any()"));
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
            $deferred->reject(new AggregateException("All promises were rejected", $errors));
        }
      );
    }

    return $deferred->promise();
  }

  // TODO: join
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