<?php
class Stream_Schema_Int_Pack2 extends Stream_Schema_Int
{
  public function __construct(
    protected string $mask    ,
    protected int    $size    ,
    protected int    $offset1 ,
    protected int    $offset2 ,
  ) {}

  function getSize(): int { return $this->size; }
  
  public function read(IStream $stream, array $context): mixed
  {
    $rawBytes = $stream->readData($this->size);
  //Logger::debug('unpack(',[$this->mask[0].'a/'.$this->mask[1].'b', $rawBytes]);
    $res = unpack($this->mask[0].'a/'.$this->mask[1].'b', $rawBytes);
  //TODO:Logger::debug($this->mask)->dump($res);
    return $res['a'] << $this->offset1
      |    $res['b'] << $this->offset2;
  }

  public function write(IStream $stream, mixed $value, array $context): int
  {
    return $stream->writeData(pack($this->mask,
      $value >> $this->offset1,
      $value >> $this->offset2
    ));
  }
}
