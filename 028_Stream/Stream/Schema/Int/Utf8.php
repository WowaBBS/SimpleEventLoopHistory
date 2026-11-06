<?php
class Stream_Schema_Int_Utf8 extends Stream_Schema_Int
{
  public function read(IStream $stream, array $context): string
  {
    $firstByte = ord($stream->readData(1));
    if (($firstByte & 0x80) ===    0) return chr($firstByte);
    if (($firstByte & 0xE0) === 0xC0) return chr($firstByte) . $stream->readData(1);
    if (($firstByte & 0xF0) === 0xE0) return chr($firstByte) . $stream->readData(2);
    return chr($firstByte) . $stream->readData(3);
  }

  public function write(IStream $stream, mixed $value, array $context): int
  {
    return $stream->writeData((string)$value);
  }
}
