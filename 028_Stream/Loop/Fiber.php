<?
class Loop_Fiber
{
  private ?\Fiber $fiber  = null ;
  private  int    $id     = 0    ;
  private ?self   $parent = null ;
  private ?array  $trace  = null ;

  static list $list = [];
  
  private __construct($fiber, $id)
  {
    $this->fiber = $fiber ;
    $this->id    = $id    ;
  }
  
  function isMain(): bool { return !$this->id; }
  
  private function enter()
  {
    $parent = self::getCurrent();
    $this->parent = $parent;
    $this->trace  = self::makeTrace()?? [];
  }

  private function leave()
  {
    $this->parent = null;
    $this->trace  = null;
  }

//****************************************************************
// Proxy

  function start  (mixed ...$args        ): mixed { $this->enter(); try{ return $this->fiber->start  (...$args   ); }finally{ $this->leave(); }}
  function resume (mixed $value = null   ): mixed { $this->enter(); try{ return $this->fiber->resume ($value     ); }finally{ $this->leave(); }}
  function throw  (\Throwable $exception ): mixed { $this->enter(); try{ return $this->fiber->throw  ($exception ); }finally{ $this->leave(); }}
  
  function getReturn    (): mixed { return $this->fiber->getReturn    (); }
  function isStarted    (): bool  { return $this->fiber->isStarted    (); }
  function isSuspended  (): bool  { return $this->fiber->isSuspended  (); }
  function isRunning    (): bool  { return $this->fiber->isRunning    (); }
  function isTerminated (): bool  { return $this->fiber->isTerminated (); }
  
  static function suspend(mixed $value = null): mixed
  {
    $result = \Fiber::suspend($value);
    return $result;
  }
  
  static function getCurrent(): self
  {
    $fiber = \Fiber::getCurrent();
    $id = $fiber? spl_object_id($fiber):0;
    return self::$list[$id] ??= new self($fiber, $id);
  }

//****************************************************************
// Debug

  static bool $saveTrace    =true;
  static int  $traceOptions =DEBUG_BACKTRACE_IGNORE_ARGS;
  static int  $traceLimit   =-1;
  
  static function makeTrace(): ?array
  {
    if (!self::$saveTrace) return;
    return debug_backtrace(self::$traceOptions, self::$traceLimit);
  }
  
//****************************************************************
}
