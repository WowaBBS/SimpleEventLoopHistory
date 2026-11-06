<?php
class Stream_Schema_Type_VarintMsb extends Stream_Schema_Int
{
  public function read(IStream $stream, array $context): int
  {
    $result = 0; $shift = 0;
    while (true)
    {
      $byte = ord($stream->readData(1));
      $result |= ($byte & 0x7F) << $shift;
      if (($byte & 0x80) === 0) break;
      $shift += 7;
    }
    return $result;
  }

  public function write(IStream $stream, mixed $value, array $context): int
  {
    $bytes = '';
    while ($value >= 0x80)
    {
      $bytes .= chr(($value & 0x7F) | 0x80);
      $value >>= 7;
    }
    $bytes .= chr($value & 0x7F);
    return $stream->writeData($bytes);
  }
}
