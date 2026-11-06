<?php
class Stream_Schema_Context
{
  private IStream $stream;
  private IStream_Codec $schema;

  public function __construct(IStream $stream, IStream_Codec $schema)
  {
    $this->stream = $stream ;
    // Если передали сырой массив, автоматически оборачиваем его в структуру
    $this->schema = $schema ;
  }
  
  public function read(): mixed
  {
    return $this->schema->read($this->stream, []);
  }

  public function write(mixed $data): int
  {
    return $this->schema->write($this->stream, $data);
  }
}