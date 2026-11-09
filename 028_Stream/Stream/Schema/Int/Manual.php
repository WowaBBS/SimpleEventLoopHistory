<?php
class Stream_Schema_Int_Manual extends Stream_Schema_Int
{
  public function __construct()
  {
  }

  public function read(IStream $stream, mixed &$value, $context): void
  {
  }

  public function write(IStream $stream, mixed $value, $context): int
  {
    return 0;
  }
}
