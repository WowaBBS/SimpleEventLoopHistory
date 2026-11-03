<?
class Stream_Until_More extends Stream_Until_Base
{
  private int $size = 0;
  
  function __construct($reader, int $size)
  {
    parent::__construct($reader);
    $this->size = $size;
  }

  function strict    ( bool $v =true) { return $this; }
  function include   (?bool $v =null) { return $this; }

  function onUntil($data, $offset): false|array
  {
    return strlen($data) > $offset + $this->size ? [0, '']:false;
  }
}
