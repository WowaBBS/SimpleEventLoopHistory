<?php
class Stream_Schema_Int_Signed extends Stream_Schema_Int
{
  private IStream_Codec $unsigned ;
  private int           $limit    ;
  
  function getSize(): int { return $this->unsigned->getSize(); }
  
  public function __construct(
    IStream_Codec $unsigned  ,
    int           $bitLength ,
  )
  {
    $this->unsigned = $unsigned;
    $this->limit    = 1 << ($bitLength-1);
  }

  public function read(IStream $stream, array $context): int
  {
    $value = $this->unsigned->read($stream, $context);
    if ($value < $this->limit) return $value;
    return $value - $this->limit <<1;
  }

  public function write(IStream $stream, mixed $value, array $context): int
  {
    return $this->unsigned->write($stream, $value, $context);
  }
}
