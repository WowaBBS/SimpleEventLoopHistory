<?
abstract class Stream_Buffer_Base implements IStream_Buffer
{
/* * /
  protected static int $limit =1024*1024;

  function getLimit(): int { return static::$limit; }
  function setLimit(int $size): void {}
/* */
  protected int $limit =1024*1024;
  
  function getLimit(): int { return $this->limit; }
  function setLimit(int $size): void { $this->limit = $size; }
/* */
  function getFree  (): int { return $this->getLimit() - $this->restSize (); }
  
  function readAll():string { return $this->read($this->restSize()); }
  
  function read (int $bytes):string
  {
    $result = $this->peek($bytes);
    $this->skip($bytes);
    return $result;
  }
  
  function until (callable $find, ?bool $include=null):false|string
  {
    $pos = $find(...$this->dataOffs());
    if ($pos === false) return false;
    [$pos, $end] = $pos;
    if ($include === true) $pos += strlen($end);
    $result = $this->read($pos);
    if ($include === null) $this->skip(strlen($end));
    return $result;
  }
}
