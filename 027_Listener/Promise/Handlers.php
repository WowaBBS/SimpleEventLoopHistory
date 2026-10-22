<?
/**
 * Handler functions
 */
class Promise_Handlers
{
  private array $handlers = [];
  static bool $delayedEvents = true;

  static function keyHandler(object|int|string $key): int|string { return is_object($key)? spl_object_id($key):$key; }
  
  function addHandler(callable $handler, null|object|int|string $key = null)
  {
    $this->handlers[self::keyHandler($key?? $handler)] = $handler;
  }

  function removeHandler(object|int|string $key)
  {
    unset($this->handlers[self::keyHandler($key)]);
  }
  
  function hasHandler(object|int|string $key)
  {
    return array_key_exists(self::keyHandler($key), $this->handlers);
  }
  
  function countHandlers() { return count($this->handlers); }

  function onlyHandler(object|int|string $key)
  {
    return count($this->handlers)===1 && $this->hasHandler($key);
  }
    
  function executeHandlers(): void
  {
    $handlers = $this->handlers;
    if (!$handlers) return;
    $this->handlers = [];

    if (static::$delayedEvents)
      $this->asyncExecuteHandlers($handlers);
    else
      $this->syncExecuteHandlers($handlers);
  }
  
  protected function asyncExecuteHandlers($handlers): void
  {
    // Отправляем выполнение обработчиков в Event Loop,
    // чтобы текущий синхронный код PHP гарантированно завершился!
    Loop::delay(0, fn()=>$this->syncExecuteHandlers($handlers)); // TODO: Workaround: Need to use task queue
  }
  
  protected function syncExecuteHandlers($handlers): void
  {
    foreach($handlers as $handler)
      try
      {
        $handler($this);
      }
      catch (\Throwable $e)
      {
        Logger::exception($e, 'Promise unexpected excetion:');
      }
      
    $handlers = $this->handlers;
    if (!$handlers) return;
    $this->handlers = [];
    
    Logger::error('Unexpected behavior, added handlers during executiong')->backtrace();
    
    $this->asyncExecuteHandlers($handlers);
  }
};