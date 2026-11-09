<?php
class Stream_Schema_Context
{
  private IStream       $stream;
  private IStream_Codec $schema;

  public function __construct(IStream $stream, IStream_Codec $schema)
  {
    $this->stream = $stream ;
    // Если передали сырой массив, автоматически оборачиваем его в структуру
    $this->schema = $schema ;
  }
  
  /**
   * Читает данные. Если привязан класс, возвращает готовый заполненный объект
   */
  public function read(mixed $result = null, array|ArrayAccess $context = []): mixed
  {
    $this->schema->read($this->stream, $result, $context);
    return $result;
  }

  /**
   * Читает данные в result
   */
  public function readTo(mixed &$result = null, array|ArrayAccess $context = []): void
  {
    $this->schema->read($this->stream, $result, $context);
  }

  /**
   * Принимает объект или массив, преобразует в бинарный вид и отправляет в сеть
   */
  public function write(mixed $data, array|ArrayAccess $context = []): int
  {
    return $this->schema->write($this->stream, $data, $context);
  }
}