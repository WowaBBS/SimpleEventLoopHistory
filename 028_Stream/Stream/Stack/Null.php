<?
class Stream_Stack_Null implements IStream_Stack, IDebug_Leak_Object
{
  static function get() { static $result = new Static(); return $result; }
  
  function leakDispose() {}

  function has(): bool { return false; }
  function isEmpty(): bool { return true; }
  function add(callable $handler, $callback = null):void {} //TODO: exception
  function call(...$args) {}  //TODO: exception
}
