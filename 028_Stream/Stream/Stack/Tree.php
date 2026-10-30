<?
class Stream_Stack_Tree extends Stream_Stack_Base
{
  var ?Stream_Stack_Node $current;
  
  function has(): bool { return (bool)$this->current; }
  function isEmpty(): bool { return $this->current == null; }
  
  function add(callable $handler, $callback = null):void
  {
    new Stream_Handler($handler, $callback)->addTo($this->current);
  }
  
  function getHandler():callable { return $this->current->handler; }
  
  function call(...$args)
  {
    $current = $this->current;
    $result = $current->invoke(...$args);
    if (self::isFinished($result))
    {
    }
    
  }
}
