<?
class Stream_Until_End extends Stream_Until_Base
{
//TODO: Error
  function strict    ( bool $v =true) { return $this; }
  function include   (?bool $v =null) { return $this; }
  
  function onUntil($data, $offset): ?array { return null; }
}
