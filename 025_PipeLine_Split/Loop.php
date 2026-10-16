<?
class Loop
{
  private static array $timers             = [];
  private static array $readStreams        = [];
  private static bool  $running            = false;
  private static array $signalCallbacks    = [];
  private static bool  $signalsInitialized = false;
  private static float $timeLimit          =0;
  private static float $hardTimeLimit      =0;
  private static float $watchingTimer      =10;
  
  static function setTimeLimit(float $seconds, float $hard=10)
  {
    self::$timeLimit = microtime(true) + $seconds;
    self::$hardTimeLimit = $hard > 0? self::$timeLimit+$hard:0;
    set_time_limit(ceil(self::$hardTimeLimit));
  }

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
   * Главный запуск цикла
   */
  static function run(): void
  {
    self::$running = true;

    while (self::$running && (!empty(self::$timers) 
                           || !empty(self::$readStreams)
                           || !empty(self::$signalCallbacks)))
    {
      // КРИТИЧНО ДЛЯ UNIX: Проверяем, не пришли ли сигналы PCNTL
      if(!str_starts_with(PHP_OS, 'WIN') && function_exists('pcntl_signal_dispatch'))
        pcntl_signal_dispatch();

      // Если во время обработки сигнала вызвали Loop::stop(), выходим
      if (!self::$running) break;

      $now = microtime(true);
      
      if(($until=self::$timeLimit) && $until<=$now)
      {
        Logger::error('Loop: TimeLimit exhausted: ', self::$running)
          ->Debug(new ReflectionClass(self::class)->getStaticProperties());
        break;
      }
      if ($limit = self::$watchingTimer)
      {
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

      // 1. Обрабатываем таймеры, у которых подошло время
      foreach (self::$timers as $key => $timer)
        if ($now >= $timer['execute_at'])
        {
          unset(self::$timers[$key]);
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
        usleep(5000); // Маленький сон, если задач нет, чтобы не перегружать CPU
    }
  }

  // Удобный хелпер для запуска кода в изолированном файбере
  static function async(callable $callback): void
  {
    $fiber = new Fiber($callback);
    // Запуск файбера. Если внутри будет Fiber::suspend(), управление вернется сюда
    $fiber->start(); 
  }
}