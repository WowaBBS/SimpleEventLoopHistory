<?
class Loop_TaskScheduler
{
  /** @var \SplQueue Queue для быстрого FIFO-выполнения без пересборки индексов массива */
  private \SplQueue $queue;
  public bool $async=true; //TODO:

  function __construct() { $this->queue = new \SplQueue(); }

  /** Добавить задачу в конец очереди */
  function enqueue(callable $callback): void
  {
    Stat::$queue->add();
    $this->queue->enqueue($callback);
  }
  
  static function safeTask($task)
  {
    try
    {
      $task();
    }
    catch (\Throwable $e)
    {
      // Ошибки тасок не должны ломать сам планировщик
      Logger::exception($e, 'There is an exception in the task');
    }
  }

  /** Выполнить все накопившиеся моментальные задачи */
  function flush(): void
  {
    while (!$this->queue->isEmpty())
    {
      $task = $this->queue->dequeue();
    //Stat::$queue->start(); //TODO:?
      
      if ($this->async)
        Loop::async(self::safeTask(...), $task);
      else
        self::safeTask($task);

      Stat::$queue->remove();
    }
  }

  function has(): bool { return !$this->queue->isEmpty(); }
}
