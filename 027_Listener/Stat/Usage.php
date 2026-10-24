<?php
use \WLib\Debug\Format as Debug;

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
    $res = ['[',
      'total' ,': ', $this->total,', ',
      'max'   ,': ', $this->max  ,
    ];
    
    if ($this->count) array_push($res, ' , count: ' ,$this->count);
    
    $res[]=']';
    return Debug::Log_List($res);
  }
}
