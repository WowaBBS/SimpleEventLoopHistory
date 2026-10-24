<?php
use \WLib\Debug\Format as Debug;

class Stat_Sleep
{
  public int   $count     =0;
  public float $sleep     =0;
  public float $oversleep =0;
  public float $insomnia  =0;
  public float $start     =0;

  function toDebug()
  {
    if(!$this->count) return [];
    $comma  = Debug::Log_Item(',', 'Op');
    $colon  = Debug::Log_Item(':', 'Op');
    $result = [Debug::Log_Item('[', 'Op'), 'count',$colon,' ', $this->count];
    if($this->sleep     ) array_push($result, $comma,' sleep'     ,$colon,' ',Debug::Time($this->sleep     ));
    if($this->oversleep ) array_push($result, $comma,' oversleep' ,$colon,' ',Debug::Time($this->oversleep ));
    if($this->insomnia  ) array_push($result, $comma,' insomnia'  ,$colon,' ',Debug::Time($this->insomnia  ));
    $result[]=Debug::Log_Item(']', 'Op');
    return Debug::Log_List($result);
  }
  
  function begin() { $this->start=Loop::getTime(); }
  function end($sleep)
  {
    $this->count++;
    $this->sleep += $sleep;
    $time = Loop::getTime()-$this->start;
    $add = $time-$sleep;
    if($add > 0)
      $this->oversleep +=$add;
    else
      $this->insomnia  -=$add;
  }
}
