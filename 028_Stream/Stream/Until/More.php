<?
class Stream_Until_More extends Stream_Until_Base
{
//TODO: Error
  function orEnd     ( bool $v =true) { return $this; }
  function include   (?bool $v =null) { return $this; }

  function onUntil($data, $offset): false|array
  {
    return strlen($data) > $offset? [0, '']:false;
  }
}
