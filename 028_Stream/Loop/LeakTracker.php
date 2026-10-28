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
  {
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
    foreach (self::$lostObjects as $object=>$backtrace)
      Logger::log('  ', $Idx++, '=>')->dump($object)->setstack($backtrace);
    Logger::log();
  }
  
  // Used in Pipeline and Promise
  static function register($object)
  {
    if (!self::$catchLostObjects) return;
    
    self::$lostObjects[$object] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
  }
  
  static function unregister($object)
  {
    unset(self::$lostObjects[$object]);
  }
}
