<?
/*
 * Memory leak detector
 */
class Loop_LeakTracker
{
  private static bool    $catchLostObjects =true;
  private static WeakMap $lostObjects      ;
  
  static function init()
  {
    self::$lostObjects=new WeakMap();
  }
  
  static function checkLost()
  {
    self::checkLostFibers();
    self::checkLostTimers();
    self::checkLostObjects();
  }
  
  static function checkLostFibers()
  { //TODO: Remove
    $fibers = Loop::LeakTracker_fetchFibers();
    if (!$fibers) return;
    Logger::error('Lost fibers:');
    foreach ($fibers as $fiber)
      Logger::log('  ', $fiber);
    Logger::log();
  }
  
  static function checkLostTimers()
  {
    $timers = Loop::LeakTracker_fetchTimers();
    if (!$timers) return;
    Logger::error('Lost timers:');
    foreach ($timers as $timer)
      Logger::log('  ', $timer);
    Logger::log();
  }
  
  static function checkLostObjects()
  {
    if (!count(self::$lostObjects)) return;
    Logger::error('Lost objects:');
    $Idx=0;
    while (count(self::$lostObjects))
      foreach (self::$lostObjects as $object=>$trace)
        try
        {
          self::unregister($object);
          $log=Logger::log('  ', $Idx++, '=>')->dump($object);
          if (is_array($trace))
            $log->setstack($trace);
          $log->done();
          $object->leakDispose();
        }
        catch(\Throwable $e)
        {
          Logger::exception($e, 'Check lost object exception:');
        }
      
    Logger::log();
  }
  
  // Used in Pipeline and Promise
  static function register(ILeakObject $object)
  {
    if (!self::$catchLostObjects) return;
    
    self::$lostObjects[$object] = self::makeTrace()?? true;
  }
  
  static function unregister(ILeakObject $object)
  {
    unset(self::$lostObjects[$object]);
  }
//****************************************************************
// Debug

  static bool $saveTrace    =true;
  static int  $traceOptions =DEBUG_BACKTRACE_IGNORE_ARGS;
  static int  $traceLimit   =-1;
  
  static function makeTrace(): ?array
  {
    if (!self::$saveTrace) return null;
    return debug_backtrace(self::$traceOptions, self::$traceLimit);
  }
  
//****************************************************************
}
