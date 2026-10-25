<?
class Loop_TimerManager
{
  private Loop_TaskScheduler $scheduler;
  private array $timers = [];
  private float $nextTimeout = 0;

  function __construct(Loop_TaskScheduler $scheduler) { $this->scheduler = $scheduler; }

  // Добавить отложенную задачу (таймер)
  function add(float $seconds, callable $callback): void
  {
    // ОПТИМИЗАЦИЯ: Если время 0, пушим сразу в менеджер задач мимо таймеров!
    if ($seconds <= 0)
    {
      $this->scheduler->enqueue($callback);
      return;
    }
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
        
        // Вместо выполнения на месте - отдаем менеджеру задач!
        $this->scheduler->enqueue($timer['callback']);
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