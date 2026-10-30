<?
class Stream_Stack_Simple extends Stream_Stack_Base
{
  private           $handler  =null;
  private ?Deferred $deferred =null;

  function has(): bool { return (bool)$this->handler; }
  function isEmpty(): bool { return !$this->handler; }
  
  function add(callable $handler, $deferred = null):void
  {
    if ($this->handler)
      throw new LogicException('handler has already exists');
    $this->handler  = $handler  ;
    $this->deferred = $deferred ;
  }

  function getHandler():callable { return $this->handler; }

  function getDeferred():?Deferred
  {
    $result = $this->deferred;
    $this->handler  = null;
    $this->deferred = null;
    return $result;
  }
}
