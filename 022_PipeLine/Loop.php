<?
class Loop
{
  private static array $timers = [];
  private static array $readStreams = [];
  private static bool $running = false;
  private static array $signalCallbacks = [];
  private static bool $signalsInitialized = false;

  // Äîáàâèòü îòëîæåííóş çàäà÷ó (òàéìåğ)
  static function delay(float $seconds, callable $callback): void
  {
    self::$timers[] = [
      'execute_at' => microtime(true) + $seconds,
      'callback' => $callback
    ];
  }

  // Ïîäïèñàòüñÿ íà ïîÿâëåíèå äàííûõ â ñîêåòå/ïîòîêå
  static function addReadStream($stream, callable $callback): void
  {
  //Echo '[Debug] resource Id: ', get_resource_id($stream), "\n";
    self::$readStreams[get_resource_id($stream)] = [
      'stream' => $stream,
      'callback' => $callback
    ];
  }

  // Îòïèñàòüñÿ îò ïîòîêà
  static function removeReadStream($stream): void
  {
  //unset(self::$readStreams[(int)$stream]);
    unset(self::$readStreams[get_resource_id($stream)]);
  }

  /**
   * Ïîäïèñêà íà ñèñòåìíûé ñèãíàë (íàïğèìåğ, SIGINT / Ctrl+C)
   */
  static function addSignal(int $signal, callable $callback): void
  {
    self::$signalCallbacks[$signal][] = $callback;
    self::initSignalHandling();
  }

  /**
   * Îñòàíîâêà öèêëà ñîáûòèé
   */
  static function stop(): void
  {
    self::$running = false;
  }

  /**
   * Èíèöèàëèçàöèÿ îáğàáîò÷èêîâ ïîä òåêóùóş ÎÑ
   */
  private static function initSignalHandling(): void
  {
    if (self::$signalsInitialized) return;

    if (str_starts_with(PHP_OS, 'WIN'))
    { // --- ÂÀĞÈÀÍÒ ÄËß WINDOWS ---
      if (function_exists('sapi_windows_set_ctrl_handler'))
        sapi_windows_set_ctrl_handler(function (int $event) {
          // PHP_WINDOWS_EVENT_CTRL_C - ıòî êîíñòàíòà íàæàòèÿ Ctrl+C ïîä Windows
          if ($event === PHP_WINDOWS_EVENT_CTRL_C)
          {
            self::triggerSignal(2); // Èìèòèğóåì SIGINT (2) äëÿ êğîññïëàòôîğìåííîñòè
            return true; // Ãîâîğèì ÎÑ, ÷òî ñîáûòèå îáğàáîòàíî
          }
          return false;
        });
    } 
    else
    { // --- ÂÀĞÈÀÍÒ ÄËß UNIX (Linux, macOS) ---
      if (function_exists('pcntl_signal'))
      { // Ğåãèñòğèğóåì ñèãíàëû SIGINT (Ctrl+C) è SIGTERM (êîìàíäà kill)
        pcntl_signal(SIGINT, fn($sig) => self::triggerSignal($sig));
        pcntl_signal(SIGTERM, fn($sig) => self::triggerSignal($sig));
      }
    }

    self::$signalsInitialized = true;
  }

  /**
   * Âûçîâ âñåõ çàğåãèñòğèğîâàííûõ êîëáıêîâ íà ıòîò ñèãíàë
   */
  private static function triggerSignal(int $signal): void
  {
    if (isset(self::$signalCallbacks[$signal]))
      foreach (self::$signalCallbacks[$signal] as $callback)
        $callback($signal);
  }

  /**
   * Ãëàâíûé çàïóñê öèêëà
   */
  static function run(): void
  {
    self::$running = true;

    while (self::$running && (!empty(self::$timers) || !empty(self::$readStreams) || !empty(self::$signalCallbacks)))
    {
      // ÊĞÈÒÈ×ÍÎ ÄËß UNIX: Ïğîâåğÿåì, íå ïğèøëè ëè ñèãíàëû PCNTL
      if(!str_starts_with(PHP_OS, 'WIN') && function_exists('pcntl_signal_dispatch'))
        pcntl_signal_dispatch();

      // Åñëè âî âğåìÿ îáğàáîòêè ñèãíàëà âûçâàëè Loop::stop(), âûõîäèì
      if (!self::$running) break;

      $now = microtime(true);

      // 1. Îáğàáàòûâàåì òàéìåğû, ó êîòîğûõ ïîäîøëî âğåìÿ
      foreach (self::$timers as $key => $timer) 
        if ($now >= $timer['execute_at'])
        {
          unset(self::$timers[$key]);
        //Echo "[Debug] Remove timer $key\n";
          ($timer['callback'])();
        }

      // 2. Ïğîâåğÿåì ñòğèìû ÷åğåç stream_select
      if (!empty(self::$readStreams))
      {
        $read = array_column(self::$readStreams, 'stream');
        $write = null;
        $except = null;
        
        // Òàéìàóò äëÿ select: áåğåì âğåìÿ äî áëèæàéøåãî òàéìåğà èëè 1 ñåêóíäó
        $timeout = 1;
        if (!empty(self::$timers))
        {
          $nextTimer = min(array_column(self::$timers, 'execute_at'));
          $timeout = max(0, $nextTimer - microtime(true));
        }

        $tv_sec = (int)$timeout;
        $tv_usec = (int)(($timeout - $tv_sec) * 1000000);

        // Æäåì ñèñòåìíîãî óâåäîìëåíèÿ î ãîòîâíîñòè ïîòîêîâ
        // Îøèáêà stream_select ïğåğûâàåò ôóíêöèş ïğè ïîëó÷åíèè ñèñòåìíîãî ñèãíàëà íà Linux,
        // ïîıòîìó ãëóøèì åå ÷åğåç @ è ïğîâåğÿåì ñòàòóñ
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
        usleep(5000); // Ìàëåíüêèé ñîí, åñëè çàäà÷ íåò, ÷òîáû íå ïåğåãğóæàòü CPU
    }
  }

  // Óäîáíûé õåëïåğ äëÿ çàïóñêà êîäà â èçîëèğîâàííîì ôàéáåğå
  static function async(callable $callback): void
  {
    $fiber = new Fiber($callback);
    // Çàïóñê ôàéáåğà. Åñëè âíóòğè áóäåò Fiber::suspend(), óïğàâëåíèå âåğíåòñÿ ñşäà
    $fiber->start(); 
  }
}