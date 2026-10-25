<?
class Loop
{
  private static array $fibers             = [];
  private static bool  $running            = false;
  private static bool  $stopping           = false; //Soft stop
  private static bool  $forceStop          = false; //Immideatly stop
  private static float $shiftTime          = 0;
  
  private static Loop_TaskScheduler $scheduler     ;
  private static Loop_TimerManager  $timers        ;
  private static Loop_IoWatcher     $io            ;
  private static Loop_SignalTracker $signals       ;
  private static Loop_WatchingTimer $watchingTimer ;
  
  static function init()
  {
    self::$shiftTime = hrtime(true);
    
    self::$scheduler     = new Loop_TaskScheduler ();
    self::$timers        = new Loop_TimerManager  (self::$scheduler);
    self::$io            = new Loop_IoWatcher     ();
    self::$signals       = new Loop_SignalTracker ();
    self::$watchingTimer = new Loop_WatchingTimer ();
    Loop_LeakTracker::init();
  }
  
  static function dispose() { Loop_LeakTracker::checkLost(); }
  
//static function getTime() { return microtime(true); }
  static function getTime() { return (hrtime(true)-self::$shiftTime)*1e-9; }
  
  /**
   * Мягная остановка цикла событий
   */
  static function stop(?float $timeout=null): void
  {
    self::$stopping = true;
    
    self::$watchingTimer->onStop($timeout);
    
    //TODO: notify tasks and remove
    if (true) self::forceStop();
  }

  /**
   * Немедленная остановка цикла событий
   */
  static function forceStop(): void { self::$forceStop = true; }

  /**
   * Выполняет ровно одну итерацию цикла событий.
   * Возвращает true, если в цикле ещё остались живые задачи (таймеры или сокеты).
   */
  static function tick():bool
  {
    if (self::$running)
    {
      Logger::error('Recursive runing')->backtrace();
      return true; //TODO: throw \LogicException?
    }
    
    self::$running = true;

    $result = self::internalTick();
        
    self::$running = false;
    
    return $result;
  }
  
  /**
   * Главный запуск цикла
   */
  static function run(): void
  {
    if (self::$running)
    {
      Logger::error('Recursive runing')->backtrace();
      return;
    }
    
    self::$running = true;
    
    while (!self::$forceStop)
      if (!self::internalTick())
        break;
    
    self::$running = false;
  }

  protected static function internalTick():bool
  {
    Stat::$ticks++;
    
    // 1. Проверяем сигналы ОС
    self::$signals->dispatch();

    // Если во время обработки сигнала вызвали Loop::stop(), выходим
    if (self::$forceStop) return false;

    $now = self::getTime();
    
    self::$watchingTimer->tick($now);
    
    // 2. Даем менеджеру таймеров проверить дедлайны и перекинуть готовые задачи в планировщик тасок
    self::$timers->check($now);

    // 3. Выполняем все накопившиеся моментальные задачи (таски из пула + проснувшиеся таймеры)
    self::$scheduler->flush();

    // 4. Спрашиваем у менеджера таймеров, сколько времени мы можем безопасно спать на stream_select
    $timeout = self::$timers->getNextTimeout();
    
    // 5. Опрашиваем дескрипторы сокетов
    self::$io->watch($timeout);
    
    // Возвращаем флаг: есть ли ради чего крутить цикл дальше
    return self::$scheduler ->has()
      ||   self::$timers    ->has()
      ||   self::$io        ->has()
      ||   self::$signals   ->has();
  }
  
  // Удобный хелпер для запуска кода в изолированном файбере
  static function async(callable $callback, ...$args): void
  {
    $callback = function() use($callback, $args)
    {
      $fiber = Fiber::getCurrent();
      $id    = spl_object_id($fiber);
      self::$fibers[$id] = $fiber;
      Stat::$fiber->add();
      try
      {
        $callback(...$args);
      }
      catch(\Throwable $e)
      {
        Logger::exception($e, 'Loopr::async{}  Fiber.exception:'); //TODO: Name;
      }
      finally
      {
        UnSet(self::$fibers[$id]);
        Stat::$fiber->remove();
      }
    };
    $fiber = new Fiber($callback);
    // Запуск файбера. Если внутри будет Fiber::suspend(), управление вернется сюда
    $fiber->start(); 
  }

//****************************************************************
// Timers

  // Добавить отложенную задачу (таймер)
  static function delay(float $seconds, callable $callback): void { self::$timers->add($seconds, $callback); }

//****************************************************************
// IO Watcher

  // Подписаться на появление данных в сокете/потоке
  static function addReadStream($stream, callable $callback): void { self::$io->addRead($stream, $callback); }

  // Отписаться от потока
  static function removeReadStream($stream): void { self::$io->removeRead($stream); }

//****************************************************************
// Signal Tracker

  /**
   * Подписка на системный сигнал (например, SIGINT / Ctrl+C)
   */
  static function addSignal(int $signal, callable $callback): void { self::$signals->add($signal, $callback); }


//****************************************************************
// Watching timer

  static function setTimeLimit(float $seconds, float $hard=10) { self::$watchingTimer->setTimeLimit($seconds, $hard); }

//****************************************************************
// Memory leak detector

  static function LeakTracker_fetchFibers() { $result = self::$fibers; self::$fibers = []; return $result; }
  static function LeakTracker_fetchTimers() { return self::$timers->LeakTracker_fetchTimers(); }

//****************************************************************
}

Loop::init();
register_shutdown_function(Loop::dispose(...));
