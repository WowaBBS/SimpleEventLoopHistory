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

  public function read(IStream $stream, array $context): int
  {
    $value = $this->unsigned->read($stream, $context);
    if ($value < $this->limit) return $value;
  //Logger::debug($value, ' signed limit ',$this->limit);
    return $value - ($this->limit <<1);
  }

  public function write(IStream $stream, mixed $value, array $context): int
  {
    return $this->unsigned->write($stream, $value, $context);
  }
}
