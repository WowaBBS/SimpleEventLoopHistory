<?php
class Stream_Schema_Type extends Stream_Schema_Node 
{
  private const FORMATS = [
    // 8-bit
    'i8'   => ['mask' => 'c', 'size' => 1],    'u8'   => ['mask' => 'C', 'size' => 1],

    // 16-bit (Big-Endian)
    'i16b' => ['mask' => 's', 'size' => 2], // PHP s зависит от архитектуры, но для строгости лучше использовать явные знаковые бинарные операции, ниже исправим это
    'u16b' => ['mask' => 'n', 'size' => 2], // Сетевой порядок

    // 16-bit (Little-Endian)
    'i16l' => ['mask' => 'v', 'size' => 2], // В PHP v беззнаковый, знаковость восстановим математически
    'u16l' => ['mask' => 'v', 'size' => 2],

    // 32-bit (Big-Endian)
    'i32b' => ['mask' => 'N', 'size' => 4], // Знаковость восстановим
    'u32b' => ['mask' => 'N', 'size' => 4],

    // 32-bit (Little-Endian)
    'i32l' => ['mask' => 'V', 'size' => 4],    'u32l' => ['mask' => 'V', 'size' => 4],

    // 64-bit (Big-Endian)
    'i64b' => ['mask' => 'J', 'size' => 8],    'u64b' => ['mask' => 'J', 'size' => 8],

    // 64-bit (Little-Endian)
    'i64l' => ['mask' => 'P', 'size' => 8],    'u64l' => ['mask' => 'P', 'size' => 8],

    // Floats
    'f32b' => ['mask' => 'G', 'size' => 4],    'f32l' => ['mask' => 'g', 'size' => 4],
    'f64b' => ['mask' => 'E', 'size' => 8],    'f64l' => ['mask' => 'e', 'size' => 8],
  ];
  
  private static array $cache = [];
  
  static function get(string $format):self { return self::$cache[$format] ??= new self($format); }
  static function lookup(string $format):?self { return self::has($format)? self::get($format):null; }
  static function has(string $format):bool { return isset(self::FORMATS[$format]); }

  public function __construct(private string $format)
  {
    if (!isset(self::FORMATS[$this->format]))
      throw new \InvalidArgumentException("Неизвестный бинарный формат: '{$this->format}'");
  }

  public function read(IStream $stream, array $context): mixed
  {
    $config = self::FORMATS[$this->format];
    // Запрашиваем у переданного стрима точное количество байт
    $rawBytes = $stream->readData($config['size']);
    $value = unpack("{$config['mask']}val", $rawBytes)['val'];

    // Корректируем знак и сохраняем в контекст
    if (str_starts_with($this->format, 'i'))
    {
      if (str_contains($this->format, '16') && $value >=     0x8000) return $value -     0x10000;
      if (str_contains($this->format, '32') && $value >= 0x80000000) return $value - 0x100000000;
    }
    return $value;
  }

  public function write(IStream $stream, mixed $value, array $context): int
  {
    $config = self::FORMATS[$this->format];
    if (is_int($value) && str_starts_with($this->format, 'i'))
    {
      if (str_contains($this->format, '16') && $value < 0) $value +=     0x10000;
      if (str_contains($this->format, '32') && $value < 0) $value += 0x100000000;
    }
    return $stream->writeData(pack($config['mask'], $value));
  }
}