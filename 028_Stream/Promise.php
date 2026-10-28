<?
//TODO: try(func), construct(callback(resolve, reject))
//   enable, Unhandled Rejections, Promise.denodeify(fn), Promise.nodeify(fn)


//Promise::all([...]) - ждет выполнения всех промисов. Если хоть один упал - падает вся цепочка.
//Promise::race([...]) - возвращает результат первого завершившегося промиса.
//Promise::resolve($value) / Promise::reject($reason) - для быстрого создания уже выполненных или отклоненных промисов.

class Promise extends Promise_Handlers
{
  private State $state = State::PENDING;
  private mixed $value = null;
  
  function isLazy       () { return $this->state === State::LAZY      ; }
  function isPending    () { return $this->state === State::PENDING   || $this->isLazy(); }
  function isFulfilled  () { return $this->state === State::FULFILLED ; }
  function isRejected   () { return $this->state === State::REJECTED  ; }
  function isSettled    () { return !$this->isPending(); }
  function isNotSettled () { return  $this->isPending(); }
  
  function __construct(mixed $arg = null)
  {
    Loop_LeakTracker::register($this);
    Stat::$promise->add();
    if ($arg === null) return;
    
    if (is_callable($arg))
    {
      Loop::async(function() {
        try
        {
          $arg(new Deferred($this));
        }
        catch(\Throwable $e)
        {
          $this->reject($e);
        }
      });
      return;
    } 
    
    if (!is_object($arg))
    {
      $this->resolve($arg);
      return;
    }
    
    if ($arg instanceof Self)
    {
      $arg->then( // TODO: handler
        $this->resolve (...),
        $this->reject  (...),
      );
    }
    
    if ($arg instanceof \Throwable)
    {
      $this->reject($arg);
      return;
    }
    
    $this->resolve($arg);
  }
  
  function __destruct()
  {
    Stat::$promise->remove();
    if ($this->isNotSettled())
      Logger::error('Destroing not settled promise:')->dump($this);
  }

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
    {
      $value = new \ReasonException((string)$value);
      static::emptyTrace($value); //Beacause of old
    }
    
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
    
    if ($reason instanceof \Throwable )
      static::optimizeTrace($reason);
    
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
  function finish(): void
  {
    try
    {
      $this->await();
    }
    catch (\Throwable $e)
    { // Глушим ошибку, задача метода finish - просто дождаться окончания выполнения
      Logger::exception($e);
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
   * Ограничивает время выполнения текущего промиса. Отменяет его по таймауту.
   * Если промис не успевает выполниться за $seconds секунд, возвращается отклонённый промис.
   */
  function timeout(float $seconds, ?string $reason = null): self
  { //TODO: Rescedule timeout
    TimeoutException::start($seconds, $reason)->catch($this->reject(...));
    return $this;
  }

  /**
   * Ограничивает время выполнения текущего промиса. Не отменяет его по таймауту.
   * Если промис не успевает выполниться за $seconds секунд, возвращается отклонённый промис.
   */
  function raceTimeout(float $seconds, ?string $reason = null): self
  {
    // Запускаем гонку между текущим промисом ($this) и промисом-таймером.
    // Возвращаем результат этой гонки.
    return self::race([$this, TimeoutException::start($seconds, $reason)]);
  }

//****************************************************************
// Handler functions

  function addHandler(callable $handler, null|object|int|string $key = null)
  {
    if ($this->isLazy()) $this->initializeLazy();
    
    parent::addHandler($handler, $key);
    
    if ($this->isPending()) return;
    
    $this->executeHandlers();
  }

//****************************************************************
// Promises static functions
  
  /**
   * Ожидает выполнения всех промисов в массиве.
   * Возвращает массив результатов в тех же ключах.
   */
  static function all (array $promises): self { return new Promise_Comb_All  ($promises)->process()->promise(); }
  
  /**
   * Возвращает промис, который завершится так же и с тем же результатом,
   * как и первый завершившийся промис из массива (успех или ошибка).
   */
  static function race(array $promises): self { return new Promise_Comb_Race ($promises)->process()->promise(); }

  /**
   * Возвращает промис, который выполнится успешно, как только выполнится успешно
   * хотя бы один из промисов в массиве.
   * Если все промисы отклонены, возвращает ошибку AggregateException.
   */
  static function any (array $promises): self { return new Promise_Comb_Any  ($promises)->process()->promise(); }
  
  /**
   * Ожидает завершения всех промисов (успешного или с ошибкой).
   * Возвращает ИСХОДНЫЙ массив промисов.
   */
  static function allSettled(array $promises): self { return self::join($promises); }
  static function join(array $promises): self { return new Promise_Comb_Join ($promises)->process()->promise(); }

//****************************************************************
// Memory leak optimize

  static function optimizeTrace($e)
  {
    $trace = $e->getTrace();
    if(!$trace) return;
    if($trace[0]['opt']?? false) return;
    $trace[0]['opt']=true;
    foreach ($trace as &$item)
    {
    //if (!array_key_exists('args', $item))
      $args=&$item['args'];
      foreach ($args as &$arg)
        switch (gettype($arg))
        {
        case 'NULL'    :
        case 'boolean' :
        case 'integer' :
        case 'double'  : break;
        case 'string'  :
          if (strlen($arg)>256)
            $arg='string'; //('.strlen($arg).')';
          break;
        case 'array'   : $arg='array'; break;
        case 'object'  : $arg=get_class($arg); break;
        default: $arg=gettype($arg);
        }
    }
  //Logger::debug('stack:')->setStack($trace);
    static::overrideTrace($e, $trace);
  }
  
  static function emptyTrace($e) { static::overrideTrace($e, []); }
  
  static function overrideTrace($e, $trace)
  {
    if(!($e instanceof \Throwable )) return;
    static $traceExc=new ReflectionProperty(\Exception ::class, 'trace');
    static $traceErr=new ReflectionProperty(\Error     ::class, 'trace');
    if($e instanceof \Exception ) $traceExc->setValue($e, $trace);
    if($e instanceof \Error     ) $traceExc->setValue($e, $trace);
  }

//****************************************************************
}