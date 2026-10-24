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
    $comma  = Debug::Log_Item(',', 'Op');
    $colon  = Debug::Log_Item(':', 'Op');
    $result = [Debug::Log_Item('[', 'Op'), 
      'total' ,$colon,' ', $this->total,$comma,' ',
      'max'   ,$colon,' ', $this->max  ,
    ];
    if($this->count) array_push($result, ' ', $comma,' count' ,$colon,' ',$this->count);
    $result[]=Debug::Log_Item(']', 'Op');
    return Debug::Log_List($result);
  }
}
