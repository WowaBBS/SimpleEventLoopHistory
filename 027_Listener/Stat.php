<?

class Stat_Usage
{
  public int $total = 0;
  public int $max   = 0;
  public int $count = 0;
  
  function add()
  {
    $this->total++;
    $this->count++;
    if($this->max<$this->count)
       $this->max=$this->count;
  }

  function remove()
  {
    $this->count--;
  }
  
  function toDebug()
  {
    if(!$this->total) return [];
    $result = (array)$this;
    if(!$this->count) unset($result['count']);
    return $result;
  }
}

class Stat
{
  public static Stat_Usage $pipeline ;
  public static Stat_Usage $fiber    ;
  public static Stat_Usage $promise  ;
  
  static function debug()
  {
    $usages = [
      'pipeline' =>self::$pipeline ,
      'fiber'    =>self::$fiber    ,
      'promise'  =>self::$promise  ,
    ];
    foreach ($usages as $name=>$usage)
      if ($debug = $usage->toDebug())
        Logger::log($name, str_repeat(' ', 8-strlen($name)), ' : ', $debug);
  }
}

Stat::$pipeline = new Stat_Usage();
Stat::$fiber    = new Stat_Usage();
Stat::$promise  = new Stat_Usage();