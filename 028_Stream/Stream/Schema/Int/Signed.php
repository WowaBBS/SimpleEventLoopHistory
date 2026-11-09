<?php
class Stream_Schema_Int_Signed extends Stream_Schema_Int
{
  private IStream_Codec $unsigned ;
  private int           $limit    ;
  
  function getSize(): int { return $this->unsigned->getSize(); }
  
  public function __construct(
    IStream_Codec $unsigned ,
    int           $limit    ,
  )
  {
    $this->unsigned = $unsigned ;
    $this->limit    = $limit    ;
  }

  public function read(IStream $stream, mixed &$value, $context): void
  {
    $this->unsigned->read($stream, $value, $context);
    if ($value >= $this->limit)
      $value -= $this->limit <<1;
  }

  public function write(IStream $stream, mixed $value, $context): int
  {
    return $this->unsigned->write($stream, $value, $context);
  }
}
