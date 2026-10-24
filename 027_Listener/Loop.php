<?
class Loop
{
  private static array $timers             = [];
  private static array $fibers             = [];
  private static bool  $running            = false;
  private static bool  $stopping           = false; //Soft stop
  private static bool  $forceStop          = false; //Immideatly stop
  private static array $signalCallbacks    = [];
  private static bool  $signalsInitialized = false;
  private static float $shiftTime          = 0;
  
  private static Loop_IoWatcher     $ioWatcher     ;
  private static Loop_WatchingTimer $watchingTimer ;
  
  static function init()
  {
    self::$shiftTime = hrtime(true);
    
    self::$ioWatcher     = new Loop_IoWatcher     ();
    self::$watchingTimer = new Loop_WatchingTimer ();
    Loop_LeakTracker::init();
  }
  
  static function dispose() { Loop_LeakTracker::checkLost(); }
  
//static function getTime() { return microtime(true); }
  static function getTime() { return (hrtime(true)-self::$shiftTime)*1e-9; }
  
  // Добавить отложенную задачу (таймер)
  static function delay(float $seconds, callable $callback): void
  {
    Stat::$timers->add();
    self::$timers[] = [
      'execute_at' => self::getTime() + $seconds,
      'callback'   => $callback
    ];
  }

  /**
   * Подписка на системный сигнал (например, SIGINT / Ctrl+C)
   */
  static function addSignal(int $signal, callable $callback): void
  {
    self::$signalCallbacks[$signal][] = $callback;
    self::initSignalHandling();
  }

  /**
   * Мягная остановка цикла событий
   */
  static function stop(?float $timeout=null): void
  {
    self::$stopping = true;
    self::setTimeLimit($timeout?? self::$$stoppingLimit);
    
    //TODO: notify tasks and remove true
    if (true || ($timeout?? 1)<=0)
    {
      self::forceStop();
      return;
    }
  }

  /**
   * Немедленная остановка цикла событий
   */
  static function forceStop(): void { self::$forceStop = true; }

  /**
   * Инициализация обработчиков под текущую ОС
   */
  private static function initSignalHandling(): void
  {
    if (self::$signalsInitialized) return;

    if (str_starts_with(PHP_OS, 'WIN'))
    { // --- ВАРИАНТ ДЛЯ WINDOWS ---
      if (function_exists('sapi_windows_set_ctrl_handler'))
        sapi_windows_set_ctrl_handler(function (int $event) {
          // PHP_WINDOWS_EVENT_CTRL_C - это константа нажатия Ctrl+C под Windows
          if ($event === PHP_WINDOWS_EVENT_CTRL_C)
          {
            self::triggerSignal(2); // Имитируем SIGINT (2) для кроссплатформенности
            return true; // Говорим ОС, что событие обработано
          }
          return false;
        });
    } 
    else
    { // --- ВАРИАНТ ДЛЯ UNIX (Linux, macOS) ---
      if (function_exists('pcntl_signal'))
      { // Регистрируем сигналы SIGINT (Ctrl+C) и SIGTERM (команда kill)
        pcntl_signal(SIGINT, fn($sig) => self::triggerSignal($sig));
        pcntl_signal(SIGTERM, fn($sig) => self::triggerSignal($sig));
      }
    }

    self::$signalsInitialized = true;
  }

  /**
   * Вызов всех зарегистрированных колбэков на этот сигнал
   */
  private static function triggerSignal(int $signal): void
  {
    if (isset(self::$signalCallbacks[$signal]))
      foreach (self::$signalCallbacks[$signal] as $callback)
        $callback($signal);
  }

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
    
    // КРИТИЧНО ДЛЯ UNIX: Проверяем, не пришли ли сигналы PCNTL
    if(!str_starts_with(PHP_OS, 'WIN') && function_exists('pcntl_signal_dispatch'))
      pcntl_signal_dispatch();

    // Если во время обработки сигнала вызвали Loop::stop(), выходим
    if (self::$forceStop) return false;

    $now = self::getTime();
    
    self::$watchingTimer->tick($now);

    // 1. Обрабатываем таймеры, у которых подошло время
    foreach (self::$timers as $key => $timer)
      if ($now >= $timer['execute_at'])
      {
        Stat::$timers->remove();
        unset(self::$timers[$key]);
        //TODO: workaround: need task manager
        Loop::async(function() use ($timer) {
          ($timer['callback'])();
        });
      }

    $timeout = 1;
    if (!empty(self::$timers))
    {
      $nextTimer = min(array_column(self::$timers, 'execute_at'));
      $timeout = max(0, $nextTimer - self::getTime());
    }

    // 2. Проверяем стримы через stream_select
    self::$ioWatcher->tick($timeout);
    
    // Возвращаем флаг: есть ли ради чего крутить цикл дальше
    return !empty(self::$timers) || self::$ioWatcher->has();
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
// IO Watcher

  // Подписаться на появление данных в сокете/потоке
  static function addReadStream($stream, callable $callback): void { self::$ioWatcher->addReadStream($stream, $callback); }

  // Отписаться от потока
  static function removeReadStream($stream): void { self::$ioWatcher->removeReadStream($stream); }

//****************************************************************
// Watching timer

  static function setTimeLimit(float $seconds, float $hard=10) { self::$watchingTimer->setTimeLimit($seconds, $hard); }

//****************************************************************
  static function LeakTracker_fetchFibers() { $result = self::$fibers; self::$fibers = []; return $result; }
  static function LeakTracker_fetchTimers() { $result = self::$timers; self::$timers = []; return $result; }
//****************************************************************
}

Loop::init();
register_shutdown_function(Loop::dispose(...));
