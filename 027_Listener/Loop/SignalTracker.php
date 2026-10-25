<?
class Loop_SignalTracker
{
  private array $callbacks   = [];
  private bool  $initialized = false;
  
  /**
   * Подписка на системный сигнал (например, SIGINT / Ctrl+C)
   */
  function add(int $signal, callable $callback): void
  {
    $this->callbacks[$signal][] = $callback;
    $this->initSignalHandling();
  }

  //function tick()
  function dispatch(): void 
  {
    // КРИТИЧНО ДЛЯ UNIX: Проверяем, не пришли ли сигналы PCNTL
    if (!str_starts_with(PHP_OS, 'WIN') && function_exists('pcntl_signal_dispatch'))
      pcntl_signal_dispatch();
  }

  /**
   * Вызов всех зарегистрированных колбэков на этот сигнал
   */
  private function trigger(int $signal): void
  {
    if (!isset($this->callbacks[$signal])) return;
    
    foreach ($this->callbacks[$signal] as $callback)
      $callback($signal);
  }

  /**
   * Инициализация обработчиков под текущую ОС
   */
  private function initSignalHandling(): void
  {
    if ($this->initialized) return;

    if (str_starts_with(PHP_OS, 'WIN'))
    { // --- ВАРИАНТ ДЛЯ WINDOWS ---
      if (function_exists('sapi_windows_set_ctrl_handler'))
        sapi_windows_set_ctrl_handler(function (int $event) {
          // PHP_WINDOWS_EVENT_CTRL_C - это константа нажатия Ctrl+C под Windows
          if ($event === PHP_WINDOWS_EVENT_CTRL_C)
          {
            $this->trigger(2); // Имитируем SIGINT (2) для кроссплатформенности
            return true; // Говорим ОС, что событие обработано
          }
          return false;
        });
    } 
    else
    { // --- ВАРИАНТ ДЛЯ UNIX (Linux, macOS) ---
      if (function_exists('pcntl_signal'))
      { // Регистрируем сигналы SIGINT (Ctrl+C) и SIGTERM (команда kill)
        pcntl_signal(SIGINT, $this->trigger(...));
        pcntl_signal(SIGTERM, $this->trigger(...));
      }
    }

    $this->initialized = true;
  }

  //TODO: Is it necessary?
  function has(): bool { return count($this->callbacks); }
}
