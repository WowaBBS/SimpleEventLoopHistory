<?php
class Stream_Schema_Prefixed extends Stream_Schema_Node
{
  private Stream_Schema_Int $lengthType;

  private static array $cache = [];
  static function get(string $format):self { return self::$cache[$format] ??= new self($format); }
  static function try(string $format):?self
  {
    // Проверяем паттерн "Префиксный тип" (Паттерн 1: p:u16b)
    if (!str_starts_with($format, 'p:')) return null;
    
    $parts = explode(':', $format, 2); // ['p', 'u16b']
    return self::get($parts[1]);
  }
  
  public function __construct(string $lengthFormat)
  {
    $this->lengthType = Stream_Schema_Factory::get($lengthFormat);
  }

  public function read(IStream $stream, mixed &$value, $context): void
  {
    // 1. Сначала скрыто читаем длину префикса
    $length = 0;
    $this->lengthType->read($stream, $length, $context);
    
    // 2. Теперь читаем саму строку на основе этой длины
    $value  = $stream->readData((int)$length);
  }

  public function write(IStream $stream, mixed $value, $context): int
  {
    $stringData = (string)$value;
    
    // 1. Автоматически упаковываем и пишем длину строки
    $bytes = $this->lengthType->write($stream, strlen($stringData), $context);
    
    // 2. Пишем саму строку
    $bytes += $stream->writeData($stringData);
    return $bytes;
  }
}