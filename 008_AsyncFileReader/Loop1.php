<?
class Loop
{
  private static array $timers = [];
  private static array $readStreams = [];
  private static bool $running = false;

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
    self::$readStreams[(int)$stream] = [
      'stream' => $stream,
      'callback' => $callback
    ];
  }

  // Îòïèñàòüñÿ îò ïîòîêà
  static function removeReadStream($stream): void
  {
    unset(self::$readStreams[(int)$stream]);
  }

  // Çàïóñòèòü öèêë
  static function run(): void
  {
    self::$running = true;

    while (self::$running && (!empty(self::$timers) || !empty(self::$readStreams)))
    {
      $now = microtime(true);

      // 1. Îáğàáàòûâàåì òàéìåğû, ó êîòîğûõ ïîäîøëî âğåìÿ
      foreach (self::$timers as $key => $timer)
      {
        if ($now >= $timer['execute_at'])
        {
          unset(self::$timers[$key]);
          ($timer['callback'])();
        }
      }

      // 2. Ïğîâåğÿåì ñîêåòû ÷åğåç stream_select
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
        if (@stream_select($read, $write, $except, $tv_sec, $tv_usec) > 0)
        {
          foreach ($read as $stream)
          {
            $key = (int)$stream;
            if (isset(self::$readStreams[$key]))
              (self::$readStreams[$key]['callback'])($stream);
          }
        }
      }
      else // Åñëè ñîêåòîâ íåò, ïğîñòî ñïèì ìèêğîñåêóíäó, ÷òîáû íå ãğóçèòü CPU íà 100%
        usleep(1000);
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