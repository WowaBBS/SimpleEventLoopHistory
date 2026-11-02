<?
class Loop_Fibers
{
  private array $active = [];
  private array $free   = [];

  function has(): bool { return !$this->active; }
  
  function kill()
  {
    if (!$this->active) return;
    $exception = new Loop_Kill();
    Logger::error('Lost fibers:');
    while ($fiber = array_first($this->active))
    {
      Logger::log('  ', $fiber);
      $fiber->throw($exception);
    }
  }
  
  function dispose()
  {
    $this->kill();
    while ($fiber = array_last($this->free))
      $fiber->resume();
  }
  
  function run(callable $task)
  {
    $fiber = array_last($this->free)?? $this->new();
    $fiber->resume($task);
  }
  
  protected function new()
  {
    $fiber = new Fiber($this->worker(...));
    $fiber->start();
    return $fiber;
  }
  
  protected function worker()
  {
    $fiber = Fiber::getCurrent();
    $id    = spl_object_id($fiber);
    
    while (1)
    {
      $this->free[$id] = $fiber;
      
      $task = Fiber::suspend(); 
      
      unset($this->free[$id]);
      
      if (!$task)
        break;
      
      Stat::$fiber->add();
      $this->active[$id] = $fiber;
      $finished = false;
      try
      {
        $task();
        $finished = true;
      }
      catch (\Throwable $e)
      {
        Logger::exception($e, 'Loopr::async{}  Fiber.exception:'); //TODO: Name;
        $finished = true;
      }
      finally
      {
        if (!$finished) Logger::error('Fiber removed without finishing');
        
        // Free memory
        try{ $task = null; } catch(\Throwable $e) { Logger::exception($e, 'Fiber: free task'); }
        
        unset($this->active[$id]);
        Stat::$fiber->remove();
      }
    }
  }

  function LeakTracker_fetchFibers() { $result = $this->active; $this->active = []; return $result; }
}
