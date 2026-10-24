<?php
use \WLib\Debug\Format as Debug;

class Stat
{
  public static Stat_Usage $timers   ;
  public static Stat_Usage $promise  ;
  public static Stat_Usage $fiber    ;
  public static Stat_Usage $pipeline ;

  public static int        $ticks    =0;
  public static Stat_Sleep $sleep    ;
  
  static function debug()
  {
    $usages = [
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

Stat::$pipeline = new Stat_Usage();
Stat::$fiber    = new Stat_Usage();
Stat::$promise  = new Stat_Usage();
Stat::$timers   = new Stat_Usage();
Stat::$sleep    = new Stat_Sleep();