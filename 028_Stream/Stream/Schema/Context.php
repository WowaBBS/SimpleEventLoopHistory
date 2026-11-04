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
    return $this->schema->read($this->stream);
  }

  public function write(mixed $data): int
  {
    return $this->schema->write($this->stream, $data);
  }

  static function normalize($node)
  {
    if ($node instanceof IStream_Codec)
      return $node;

    // Сценарий 1: Число - читаем фиксированную строку байт
    if (is_int($node))
      return new Stream_Schema_Fixed($node);
    
    // Сценарий 2: Вычисляемое динамическое поле
    //TODO: ?if (is_callable($node)) return new Stream_Schema_Func($format);
    
    // Сценарий 3: Вложенная структура
    if (is_array ($node))
      return new Stream_Schema_Struct($node);

    // Сценарий 4: Наш красивый бинарный тип (u8, i32b, etc.)
    if (is_string ($node))
      return Stream_Schema_Type::lookup($node)
        ??Stream_Schema_Prefixed::lookup($node)
        ??throw new \LogicException("Неизвестный бинарный формат: '$node'");

    throw new \LogicException("Поле '{$key}' должно реализовывать интерфейс IStream_Codec");
  }
}