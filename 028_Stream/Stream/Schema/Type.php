<?php
class Stream_Schema_Type extends Stream_Schema_Node 
{
  private const FORMATS = [
    // --- СИМВОЛЫ И СТРОКИ (CHAR/UTF) ---
    'c1'   => ['size' => 1, 'custom' => true, 'type' => 'c8'],
    'c2'   => ['size' => 2, 'custom' => true, 'type' => 'c16'],
    'c4'   => ['size' => 4, 'custom' => true, 'type' => 'c32'],
    'utf8' => ['size' => 0, 'custom' => true, 'type' => 'utf8'], // Динамический размер
    'utf2b'=> ['size' => 2, 'custom' => true, 'type' => 'utf16b'],
    'utf2l'=> ['size' => 2, 'custom' => true, 'type' => 'utf16l'],

    // --- ПЕРЕМЕННАЯ ДЛИНА (VARINT) ---
    'v_utf8' => ['size' => 0, 'custom' => true, 'type' => 'v_utf8'], // Кодирование как в UTF-8
    'v_msb'  => ['size' => 0, 'custom' => true, 'type' => 'v_msb'],  // Старший бит (7-бит кодирование)
  ];
  
  private static array $cache = [];
  static function get(string $format):self { return self::$cache[$format] ??= new self($format); }
  static function try(string $format):?self { return self::has($format)? self::get($format):null; }
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