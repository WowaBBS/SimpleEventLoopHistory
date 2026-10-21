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
  
  function isLazy       () { return $this->state === State::LAZY      ; }
  function isPending    () { return $this->state === State::PENDING   || $this->isLazy(); }
  function isFulfilled  () { return $this->state === State::FULFILLED ; }
  function isRejected   () { return $this->state === State::REJECTED  ; }
  function isSettled    () { return !$this->isPending(); }
  function isNotSettled () { return  $this->isPending(); }

  /**
   * Синхронное получение значения БЕЗ вызова await().
   * Выбрасывает исключение, если промис еще не выполнен или выполнен с ошибкой.
   */
  public function getValue(): mixed
  {
    if (!$this->isFulfilled())
      throw new \LogicException('Cannot call getValue(), because the promise is in the following state: '.$this->state->name);

    return $this->value;
  }
  
  function getReason():\Throwable
  {
    if (!$this->isRejected())
      throw new \LogicException('Cannot call getReason(), because the promise is in the following state: '.$this->state->name);
      
    $value = $this->value;
    if (!($value instanceof \Throwable))
      $value = new \RuntimeException((string)$value);
    
    return $value;
  }
  
  function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
  {
    $deferred = new Deferred();

    $this->addHandler(fn($promise)=>$promise->thenHandler($deferred, $onFulfilled, $onRejected), $deferred->promise());

    return $deferred->promise();
  }
  
  function finally(callable $onFinally)
  {
    $deferred = new Deferred();
    
    $callback = function($promise) use($deferred, $onFinally)
    {
      if (!$promise->isSettled()) return;
      
      try
      {
        $onFinally();
        $deferred->resolve();
      }
      catch(\Throwable $e)
      {
        $deferred->reject($e);
      }
    };
    
    $this->addHandler($callback, $deferred->promise());
      
    return $deferred->promise();
  }

  function catch(callable $onRejected): self
  {
    return $this->then(null, $onRejected);
  }

  function resolve(mixed $value=null): void
  {
    if (!$this->isPending()) return;
    
    if ($value instanceof self)
    {  // Если другой промис, ждем его
      $value->then(
        $this->resolve (...),
        $this->reject  (...),
      );
      return;
    }
    
    $this->state = State::FULFILLED;
    $this->value = $value;
    $this->executeHandlers();
  }

  function reject(mixed $reason): void
  {
    if (!$this->isPending()) return;
    
    $this->state = State::REJECTED;
    $this->value = $reason;
    $this->executeHandlers();
  }

  function await(): mixed
  {
    // Если промис уже выполнен, сразу возвращаем значение (без паузы)
    if ($this->isFulfilled ()) return $this->value;
    if ($this->isRejected  ()) throw $this->getReason();

    // --- СЦЕНАРИЙ 1: Мы находимся внутри Файбера ---    
    if($currentFiber = \Fiber::getCurrent())
    {
      // Подписываемся на этот же промис
      $this->then(
        // Когда выполнится - возобновляем файбер и передаем результат
        fn($value    ) => !$currentFiber->isTerminated() && $currentFiber->resume($value),
        // Если ошибка - возобновляем файбер, бросая в него исключение
        fn($exception) => !$currentFiber->isTerminated() && $currentFiber->throw($exception),
      );

      // Приостанавливаем файбер. Управление возвращается в основной поток (Event Loop)
      // Когда вызовется resume() или throw(), код продолжится со следующей строки
      return \Fiber::suspend();
    }
    
    // --- СЦЕНАРИЙ 2: Мы в основном потоке (НЕ в файбере) ---    
    // Просто крутим цикл событий до тех пор, пока этот промис не завершится
    while ($this->isNotSettled())
    {
      $hasWork = Loop::tick();
      
      // Защита от вечного цикла: если в Loop кончились задачи (таймеры/сокеты), 
      // а промис так и не разрешился - это взаимная блокировка (Deadlock)
      if (!$hasWork && $this->isNotSettled())
        throw new \LogicException('Deadlock detected: Execution has been halted because there are no active tasks in the Event Loop to resolve this promise.');
    }

    // Возвращаем результат или бросаем ошибку
    if($this->isFulfilled()) return $this->value;
    throw $this->getReason();    
  }
  
  /**
   * Метод finish(). Блокирует текущий файбер до завершения промиса.
   * Ничего не возвращает (void) и глушит любые исключения внутри себя,
   * гарантируя просто завершение асинхронного процесса.
   */
  public function finish(): void
  {
    try
    {
      $this->await();
    }
    catch (\Throwable)
    { // Глушим ошибку, задача метода finish - просто дождаться окончания выполнения
    }
  }

//****************************************************************
// Lazy initializer

  /**
   * Вариант 1: Коллбэк принимает Deferred и аргументы напрямую
   */
  public static function lazyDeferred(callable $onCreate, mixed ...$args): self
  {
    $promise = new self();
    $promise->state = State::LAZY;
    
    // Капсулируем всё в одну легкую Closure
    $promise->value = fn($promise)=>$onCreate(new Deferred($promise), ...$args);

    return $promise;
  }

  /**
   * Вариант 2: Коллбэк выполняет запрос и возвращает другой Promise
   */
  public static function lazy(callable $onCreate, mixed ...$args): self 
  {
    $promise = new self();
    $promise->state = State::LAZY;
    $promise->value = fn($promise)=>$promise->resolve($onCreate(...$args));
    return $promise;
  }
  
  private function initializeLazy(): void
  {
    $initializer = $this->value;
    $this->value= null; // Освобождаем память от замыкания
  
    // Переводим в PENDING ДО вызова колбэка, чтобы избежать бесконечной рекурсии
    $this->state = State::PENDING;
    
    $initializer($this);
  }

//****************************************************************
// Predefined handlers

  private function thenHandler($deferred, $onFulfilled, $onRejected)
  {
    if (!$this->isSettled()) return; //Nod is impossible
    $isOk  = $this->isFulfilled ();
    $value = $this->value;
  
    if($callback = $isOk? $onFulfilled:$onRejected)
      try
      {
        $value = $callback($value);
        $isOk  = true;
      }
      catch (Throwable $e)
      {
        $value = $e;
        $isOk  = false;
      }
      
    if ($isOk)
      $deferred->resolve($value);
    else
      $deferred->reject($value);
  }
  

//****************************************************************
// Abort functional

  // Метод для установки колбэка очистки (используется внутри AsyncFile/httpGet)
  function onReject(callable $callback, ?Object $who=null): self
  {
    $this->addHandler(fn($promise)=>$promise->isRejected() && $callback($promise->getReason()), $who);
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
    if ($this->isSettled()) return;

    // Отклоняем промис с ошибкой AbortException
    $this->reject(new AbortException($reason ?? 'Promise aborted.'));
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

//****************************************************************
// Handler functions

  private array $handlers = [];
  static bool $delayedEvents = false;

  static function keyHandler(object|int|string $key): int|string { return is_object($key)? spl_object_id($key):$key; }
  
  function addHandler(callable $handler, null|object|int|string $key = null)
  {
    if ($this->isLazy()) $this->initializeLazy();
    
    $this->handlers[self::keyHandler($key?? $handler)] = $handler;
    
    if ($this->isPending()) return;
    
    $this->executeHandlers();
  }

  function removeHandler(object|int|string $key)
  {
    unset($this->handlers[self::keyHandler($key)]);
  }
  
  function hasHandler(object|int|string $key)
  {
    return array_key_exists(self::keyHandler($key), $this->handlers);
  }
  
  function executeHandlers(): void
  {
    $handlers = $this->handlers;
    if (!$handlers) return;
    $this->handlers = [];

    if (!static::$delayedEvents) { $this->syncExecuteHandlers($handlers); return; }

    $this->asyncExecuteHandlers($handlers);
  }
  
  protected function asyncExecuteHandlers($handlers): void
  {
    // Отправляем выполнение обработчиков в Event Loop,
    // чтобы текущий синхронный код PHP гарантированно завершился!
    Loop::delay(0, fn()=>$this->syncExecuteHandlers($handlers)); // TODO: Workaround: Need to use task queue
  }
  
  protected function syncExecuteHandlers($handlers): void
  {
    foreach($handlers as $handler)
      try
      {
        $handler($this);
      }
      catch (\Throwable $e)
      {
        Logger::exception($e, 'Promise unexpected excetion:');
      }
      
    $handlers = $this->handlers;
    if (!$handlers) return;
    $this->handlers = [];
    
    Logger::error('Unexpected behavior, added handlers during executiong')->backtrace();
    
    $this->asyncExecuteHandlers($handlers);
  }
  
//****************************************************************
// Promises static functions
  
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

  /**
   * Ожидает завершения всех промисов (успешного или с ошибкой).
   * Возвращает ИСХОДНЫЙ массив промисов.
   */
  static function allSettled(array $promises): self { return self::join($promises); }
  static function join(array $promises): self
  {
    $deferred = new Deferred();

    $remaining = 1; // 1 -- is myself
    
    $callback=function($promise) use (&$remaining, $deferred, $promises)
    {
      if ($promise?->isNotSettled()) return;
      
      $remaining--;
      
      if ($remaining > 0) return;
      
      $deferred->resolve($promises); // Возвращаем те же промисы!
    };

    foreach ($promises as $promise)
    {
      if (!($promise instanceof self)) continue;
      if ($promise->isSettled()) continue;
      
      $remaining++;
      
      // Нам важен сам факт завершения промиса (settle),
      // поэтому в оба канала вешаем один и тот же счетчик
      $promise->addHandler($callback, $deferred->promise());
    }

    $callback(null); // resolve myself

    return $deferred->promise();
  }

//****************************************************************
}