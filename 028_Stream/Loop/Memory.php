<?
class Loop_MemoryLoop
{
  protected self $loop ;
  
  function id():int { return spl_object_id($this); }
  function __construct(callable $callback) { $this->loop = $this; Loop_Memory::_onCreate($this->id(), $callback); }
  function __destruct() { Loop_Memory::_onDestroy($this->id()); }
  function __invoke() {} // Recurseve
}

class Loop_Memory
{
  static array $callbacks =[];
  static int   $levelCoef =2; //TODO: calc
  
  static function dispose() { }

  static function onGC(callable $callback, int $count=1)
  {
    $count *= self::$levelCoef;
    if ($count<=0) { $callback(); return; }
    for (;$count > 0; $count--)
      $callback = new Loop_MemoryLoop($callback);
  }
  
  static function _onCreate(int $id, callable $callback)
  {
    self::$callbacks[$id] = $callback;
  }
  
  static function _onDestroy(int $id)
  {
    try
    {
      $callback = self::$callbacks[$id]?? null;
      if (!$callback) return;
      unset(self::$callbacks[$id]);
      $callback();
    }
    catch(\Throwable $e)
    {
      Logger::exception($e, 'Loop_Memory::callback: ', $callback);
    }
  }
};