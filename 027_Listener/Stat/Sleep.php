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
    $res = ['[count: ', $this->count];
    if ($this->sleep     ) array_push($res, ', sleep'     ,': ',Debug::Time($this->sleep     ));
    if ($this->oversleep ) array_push($res, ', oversleep' ,': ',Debug::Time($this->oversleep ));
    if ($this->insomnia  ) array_push($res, ', insomnia'  ,': ',Debug::Time($this->insomnia  ));
    $res[]=']';
    return Debug::Log_List($res);
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
