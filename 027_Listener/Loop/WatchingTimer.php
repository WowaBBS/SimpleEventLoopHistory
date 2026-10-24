<?
/*
 * Watching timer
 */
class Loop_WatchingTimer
{
  private float $timeLimit          =  0;
  private float $hardTimeLimit      =  0;
  private float $watchingTimer      = 10;
  private float $stoppingLimit      = 10;
  
  function setTimeLimit(float $seconds, float $hard=10)
  {
    $this->timeLimit = Loop::getTime() + $seconds;
    $this->hardTimeLimit = $hard > 0? $this->timeLimit+$hard:0;
    set_time_limit(ceil($this->hardTimeLimit));
  }

  function tick($now)
  {
    if(($until=$this->timeLimit) && $until<=$now)
    {
      Logger::error('Loop: TimeLimit exhausted');
      //->Dump(new ReflectionClass(Loop::class)->getStaticProperties());
      Loop::forceStop();
      return;
    }
    
    if ($limit = $this->watchingTimer)
    { //TODO: period 1 second
      if ($until = $this->hardTimeLimit)
      {
        $until -= $now;
        if ($limit > $until)
          $limit = $until;
      }
      
      if ($limit > 0)
        set_time_limit(ceil($limit));
    }
  }
}
