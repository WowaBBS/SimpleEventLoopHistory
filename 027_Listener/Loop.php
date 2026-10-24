<?
class Loop
{
  private static array $timers             = [];
  private static array $fibers             = [];
  private static array $readStreams        = [];
  private static bool  $running            = false;
  private static bool  $stopping           = false; //Soft stop
  private static bool  $forceStop          = false; //Immideatly stop
  private static array $signalCallbacks    = [];
  private static bool  $signalsInitialized = false;
  private static float $shiftTime          = 0;
  
  static function init()
  {
    self::$shiftTime = hrtime(true);
    self::checkLostInit();
  }
  static function dispose() { self::checkLost(); }
  
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

  // Подписаться на появление данных в сокете/потоке
  static function addReadStream($stream, callable $callback): void
  {
    self::$readStreams[get_resource_id($stream)] = [
      'stream'   => $stream,
      'callback' => $callback
    ];
  }

  // Отписаться от потока
  static function removeReadStream($stream): void
  {
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
    
    self::watchingTick($now);

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
    if (!empty(self::$readStreams))
    {
      $read = array_column(self::$readStreams, 'stream');
      $write = null;
      $except = null;
      
      // Таймаут для select: берем время до ближайшего таймера или 1 секунду
      $tv_sec = (int)$timeout;
      $tv_usec = (int)(($timeout - $tv_sec) * 1000000);

      // Ждем системного уведомления о готовности потоков
      // Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
      // поэтому глушим ее через @ и проверяем статус
      
      Stat::$sleep->begin();
      $count = @stream_select($read, $write, $except, $tv_sec, $tv_usec);
      Stat::$sleep->end($timeout);

      if ($count > 0)
        foreach ($read as $stream)
        {
          $key = get_resource_id($stream);
          if (isset(self::$readStreams[$key]))
            (self::$readStreams[$key]['callback'])($stream);
        }
    }
    else //$sleep
    {
      Stat::$sleep->begin();
      usleep((int)($timeout*1000000)); // Маленький сон, если задач нет, чтобы не перегружать CPU
      Stat::$sleep->end($timeout);
    }
    
    // Возвращаем флаг: есть ли ради чего крутить цикл дальше
    return !empty(self::$timers) || !empty(self::$readStreams);
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
// Watching timer

  private static float $timeLimit          =  0;
  private static float $hardTimeLimit      =  0;
  private static float $watchingTimer      = 10;
  private static float $stoppingLimit      = 10;
  
  static function setTimeLimit(float $seconds, float $hard=10)
  {
    self::$timeLimit = self::getTime() + $seconds;
    self::$hardTimeLimit = $hard > 0? self::$timeLimit+$hard:0;
    set_time_limit(ceil(self::$hardTimeLimit));
  }

  static function watchingTick($now)
  {
    if(($until=self::$timeLimit) && $until<=$now)
    {
      Logger::error('Loop: TimeLimit exhausted');
      //->Dump(new ReflectionClass(self::class)->getStaticProperties());
      self::forceStop();
      return;
    }
    
    if ($limit = self::$watchingTimer)
    { //TODO: period 1 second
      if ($until = self::$hardTimeLimit)
      {
        $until -= $now;
        if ($limit > $until)
          $limit = $until;
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
    self::$lostObjects=new WeakMap();
  }
  
  static function checkLost()
  {
    self::checkLostFibers();
    self::checkLostTimers();
    self::checkLostObjects();
  }
  
  static function checkLostFibers()
  {
    if (!self::$timers) return;
    Logger::error('Lost fibers:');
    foreach (self::$fibers as $fiber)
      Logger::log('  ', $fiber);
    Logger::log();
    self::$timers=[];
  }
  
  static function checkLostTimers()
  {
    if (!self::$timers) return;
    Logger::error('Lost timers:');
    foreach (self::$timers as $timer)
      Logger::log('  ', $timer);
    Logger::log();
    self::$timers=[];
  }
  
  static function checkLostObjects()
  {
    if (!count(self::$lostObjects)) return;
    Logger::error('Lost objects:');
    $Idx=0;
    foreach (self::$lostObjects as $object=>$backtrace)
      Logger::log('  ', $Idx++, '=>')->dump($object)->setstack($backtrace);
    Logger::log();
  }
  
  
  static function register($object)
  {
    if (!self::$catchLostObjects) return;
    
    self::$lostObjects[$object] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
  }
  
  static function unregister($object)
  {
    unset(self::$lostObjects[$object]);
  }

//****************************************************************
}

Loop::init();
register_shutdown_function(Loop::dispose(...));
