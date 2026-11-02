<?php
class System_Memory_Loop
{
  protected self $loop ;
  
  function id():int { return spl_object_id($this); }
  function __construct(callable $callback) { $this->loop = $this; System_Memory_GC::_onCreate($this->id(), $callback); }
  function __destruct() { System_Memory_GC::_onDestroy($this->id()); }
  function __invoke() {} // Recurseve
}
