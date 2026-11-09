<?php
class Stream_Schema_Int_Pack extends Stream_Schema_Int
{
  public function __construct(
    protected string $mask,
    protected int    $size
  ) {}

  function getSize(): int { return $this->size; }
  
  public function read(IStream $stream, mixed &$value, $context): void
  {
    $rawBytes = $stream->readData($this->size);
    $value    = unpack($this->mask, $rawBytes)[1];
  }

  public function write(IStream $stream, mixed $value, $context): int
  {
    return $stream->writeData(pack($this->mask, $value));
  }
}
