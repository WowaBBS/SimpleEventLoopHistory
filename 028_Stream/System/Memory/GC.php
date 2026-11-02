<?
class System_Memory_GC
{
  static array $callbacks =[];
  static int   $levelCoef =2; //TODO: calc
  
  static function dispose() { }

  static function onGC(callable $callback, int $count=1)
  {
    $count *= self::$levelCoef;
    if ($count<=0) { $callback(); return; }
    for (;$count > 0; $count--)
      $callback = new System_Memory_Loop($callback);
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
      Logger::exception($e, 'System_Memory_GC::callback: ', $callback);
    }
  }
};