<?
class Loop_Fibers
{
  private array $active = [];
  private array $free   = [];

  function has(): bool { return !$this->active; }
  
  function dispose()
  {
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
      try
      {
        $task();
      }
      catch (\Throwable $e)
      {
        Logger::exception($e, 'Loopr::async{}  Fiber.exception:'); //TODO: Name;
      }
      finally
      {
        // Free memory
        try{ $task = null; } catch(\Throwable $e) { Logger::exception($e, 'Fiber: free task'); }
        unset($this->active[$id]);
        Stat::$fiber->remove();
      }
    }
  }

  function LeakTracker_fetchFibers() { $result = $this->active; $this->active = []; return $result; }
}
