<?
class Loop
{
  private static array $timers             = [];
  private static array $fibers             = [];
  private static array $readStreams        = [];
  private static bool  $running            = true;
  private static array $signalCallbacks    = [];
  private static bool  $signalsInitialized = false;
  
  static function init() { static::checkLostInit(); }
  static function dispose() { static::checkLost(); }
  
  // Добавить отложенную задачу (таймер)
  static function delay(float $seconds, callable $callback): void
  {
    self::$timers[] = [
      'execute_at' => microtime(true) + $seconds,
      'callback' => $callback
    ];
  }

  // Подписаться на появление данных в сокете/потоке
  static function addReadStream($stream, callable $callback): void
  {
    self::$readStreams[get_resource_id($stream)] = [
      'stream' => $stream,
      'callback' => $callback
    ];
  }

  // Отписаться от потока
  static function removeReadStream($stream): void
  {
  //unset(self::$readStreams[(int)$stream]);
    unset(self::$readStreams[get_resource_id($stream)]);
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
   * Остановка цикла событий
   */
  static function stop(): void
  {
    self::$running = false;
  }

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
  static function tick()
  {
    // КРИТИЧНО ДЛЯ UNIX: Проверяем, не пришли ли сигналы PCNTL
    if(!str_starts_with(PHP_OS, 'WIN') && function_exists('pcntl_signal_dispatch'))
      pcntl_signal_dispatch();

    // Если во время обработки сигнала вызвали Loop::stop(), выходим
  //if (!self::$running) return false;

    $now = microtime(true);
    
    static::watchingTick($now);

    // 1. Обрабатываем таймеры, у которых подошло время
    foreach (self::$timers as $key => $timer)
      if ($now >= $timer['execute_at'])
      {
        unset(self::$timers[$key]);
        //TODO: workaround: need task manager
        Loop::async(function() use ($timer) {
          ($timer['callback'])();
        });
      }

    // 2. Проверяем стримы через stream_select
    if (!empty(self::$readStreams))
    {
      $read = array_column(self::$readStreams, 'stream');
      $write = null;
      $except = null;
      
      // Таймаут для select: берем время до ближайшего таймера или 1 секунду
      $timeout = 1;
      if (!empty(self::$timers))
      {
        $nextTimer = min(array_column(self::$timers, 'execute_at'));
        $timeout = max(0, $nextTimer - microtime(true));
      }

      $tv_sec = (int)$timeout;
      $tv_usec = (int)(($timeout - $tv_sec) * 1000000);

      // Ждем системного уведомления о готовности потоков
      // Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
      // поэтому глушим ее через @ и проверяем статус
      if(@stream_select($read, $write, $except, $tv_sec, $tv_usec) > 0)
        foreach ($read as $stream)
        {
        //$key = (int)$stream;
          $key = get_resource_id($stream);
          if (isset(self::$readStreams[$key]))
            (self::$readStreams[$key]['callback'])($stream);
        }
    }
    else
      usleep(1000); // Маленький сон, если задач нет, чтобы не перегружать CPU
    
    // Возвращаем флаг: есть ли ради чего крутить цикл дальше
    return !empty(self::$timers) || !empty(self::$readStreams);
  }
  
  /**
   * Главный запуск цикла
   */
  static function run(): void
  {
  //self::$running = true;
    while (self::$running)
      if (!static::tick())
        break;
  }

  // Удобный хелпер для запуска кода в изолированном файбере
  static function async(callable $callback, ...$args): void
  {
    $callback = function() use($callback, $args)
    {
      $fiber=Fiber::getCurrent();
      $id=spl_object_id($fiber);
      self::$fibers[$id]=$fiber;
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
      }
    };
    $fiber = new Fiber($callback);
    // Запуск файбера. Если внутри будет Fiber::suspend(), управление вернется сюда
    $fiber->start(); 
  }

//****************************************************************
// Watching timer

  private static float $timeLimit          =  0;
  private static float $hardTimeLimit      =  0;
  private static float $watchingTimer      = 10;
  
  static function setTimeLimit(float $seconds, float $hard=10)
  {
    self::$timeLimit = microtime(true) + $seconds;
    self::$hardTimeLimit = $hard > 0? self::$timeLimit+$hard:0;
    set_time_limit(ceil(self::$hardTimeLimit));
  }

  static function watchingTick($now)
  {
    if(($until=self::$timeLimit) && $until<=$now)
    {
      Logger::error('Loop: TimeLimit exhausted: ', self::$running)
        ->Dump(new ReflectionClass(self::class)->getStaticProperties());
      return false;
    }
    
    if ($limit = self::$watchingTimer)
    { //TODO: period 1 second
      $until=self::$hardTimeLimit;
      if ($until)
      {
        $until-=$now;
        if($limit>$until)
          $limit=$until;
      }
      if ($limit > 0)
        set_time_limit(ceil($limit));
    }
  }

//****************************************************************
// Memory leak detector

  private static bool    $catchLostObjects =true;
  private static WeakMap $lostObjects      ;
  
  static function checkLostInit()
  {
    static::$lostObjects=new WeakMap();
  }
  
  static function checkLost()
  {
    static::checkLostFibers();
    static::checkLostTimers();
    static::checkLostObjects();
  }
  
  static function checkLostFibers()
  {
    if (!static::$timers) return;
    Logger::error('Lost fibers:');
    foreach (static::$fibers as $fiber)
      Logger::log('  ', $fiber);
    Logger::log();
    static::$timers=[];
  }
  
  static function checkLostTimers()
  {
    if (!static::$timers) return;
    Logger::error('Lost timers:');
    foreach (static::$timers as $timer)
      Logger::log('  ', $timer);
    Logger::log();
    static::$timers=[];
  }
  
  static function checkLostObjects()
  {
    if (!count(static::$lostObjects)) return;
    Logger::error('Lost objects:');
    $Idx=0;
    foreach (static::$lostObjects as $object=>$backtrace)
      Logger::log('  ', $Idx++, '=>')->dump($object)->setstack($backtrace);
    Logger::log();
  }
  
  
  static function register($object)
  {
    if (!static::$catchLostObjects) return;
    
    static::$lostObjects[$object]=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
  }
  
  static function unregister($object)
  {
    unset(static::$lostObjects[$object]);
  }

//****************************************************************
}

Loop::init();
register_shutdown_function(Loop::dispose(...));
