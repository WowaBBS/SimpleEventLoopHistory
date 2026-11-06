<?php
class Stream_Schema_Int_Pack extends Stream_Schema_Int
{
  public function __construct(
    protected string $mask,
    protected int    $size
  ) {}

  function getSize(): int { return $this->size; }
  
  public function read(IStream $stream, array $context): mixed
  {
    $rawBytes = $stream->readData($this->size);
  //return unpack("{$this->mask}val", $rawBytes)['val'];
    return unpack($this->mask, $rawBytes)[1];
  }

  public function write(IStream $stream, mixed $value, array $context): int
  {
    return $stream->writeData(pack($this->mask, $value));
  }
}
