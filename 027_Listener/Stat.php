<?php
use \WLib\Debug\Format as Debug;

class Stat
{
  public static Stat_Usage $queue    ;
  public static Stat_Usage $timers   ;
  public static Stat_Usage $promise  ;
  public static Stat_Usage $fiber    ;
  public static Stat_Usage $pipeline ;

  public static int        $ticks    =0;
  public static Stat_Sleep $sleep    ;
  
  static function init()
  {
    self::$queue    = new Stat_Usage();
    self::$timers   = new Stat_Usage();
    self::$promise  = new Stat_Usage();
    self::$fiber    = new Stat_Usage();
    self::$pipeline = new Stat_Usage();
    self::$sleep    = new Stat_Sleep();
  }
  
  static function debug()
  {
    $usages = [
      'queue'    =>self::$queue    ,
      'timers'   =>self::$timers   ,
      'promise'  =>self::$promise  ,
      'fiber'    =>self::$fiber    ,
      'pipeline' =>self::$pipeline ,
      'sleep'    =>self::$sleep    ,
    ];
    foreach ($usages as $name=>$usage)
      if ($debug = $usage->toDebug())
        Logger::log($name, str_repeat(' ', 8-strlen($name)), ' : ', $debug);
    
    if (self::$sleep || self::$ticks || self::$timers)
      Logger::log(
        'ticks: ', self::$ticks, ', ',
        'total: ', Debug::Time(Loop::getTime()),
      );
  }
}

Stat::init();
