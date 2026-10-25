<?
class Loop_TimerManager
{
  private array $timers = [];
  private float $nextTimeout = 0;

  // Добавить отложенную задачу (таймер)
  function add(float $seconds, callable $callback): void
  {
    Stat::$timers->add();
    $this->timers[] = [
      'execute_at' => Loop::getTime() + $seconds,
      'callback'   => $callback
    ];
  }
  
  function check($now)
  {
    // 1. Обрабатываем таймеры, у которых подошло время
    foreach ($this->timers as $key => $timer)
      if ($now >= $timer['execute_at'])
      {
        Stat::$timers->remove();
        unset($this->timers[$key]);
        //TODO: workaround: need task manager
        Loop::async(function() use ($timer) {
          ($timer['callback'])();
        });
      }
  }

  function getNextTimeout(): float
  { //TODO: Optimize
    $timeout = 1;
    if (!empty($this->timers))
    {
      $nextTimer = min(array_column($this->timers, 'execute_at'));
      $timeout   = max(0, $nextTimer - Loop::getTime());
    }
    $this->nextTimeout = $timeout;
    return $this->nextTimeout;
  }
  
  function has(): bool { return count($this->timers); }

//****************************************************************
// Memory leak detector

  function LeakTracker_fetchTimers() { $result = $this->timers; $this->timers = []; return $result; }
  
//****************************************************************
}