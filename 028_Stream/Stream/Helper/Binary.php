<?php
use Stream_IO_Writer as IWriter;
use Stream_IO_Reader as IReader;

class Stream_Helper_Binary
{
  private const BINARY_FORMATS = [
    // 8-bit
    'i8'   => ['mask' => 'c', 'size' => 1],
    'u8'   => ['mask' => 'C', 'size' => 1],

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
    'i32l' => ['mask' => 'V', 'size' => 4],
    'u32l' => ['mask' => 'V', 'size' => 4],

    // 64-bit (Big-Endian)
    'i64b' => ['mask' => 'J', 'size' => 8],
    'u64b' => ['mask' => 'J', 'size' => 8],

    // 64-bit (Little-Endian)
    'i64l' => ['mask' => 'P', 'size' => 8],
    'u64l' => ['mask' => 'P', 'size' => 8],

    // Floats
    'f32b' => ['mask' => 'G', 'size' => 4],
    'f32l' => ['mask' => 'g', 'size' => 4],
    'f64b' => ['mask' => 'E', 'size' => 8],
    'f64l' => ['mask' => 'e', 'size' => 8],
  ];
  
  /**
   * Статический метод для рекурсивного разбора бинарной структуры из стрима
   */
  public static function parseStructure(IStream|IReader $stream, array $formatMap): array 
  {
    return self::processMap($stream, $formatMap, []);
  }  
  
  private static function processMap(IStream|IReader $stream, array $formatMap, array $context): array
  {
    foreach ($formatMap as $key => $format)
    {
      // Сценарий 1: Число - читаем фиксированную строку байт
      if (is_int($format)) { $context[$key] = $stream->readData($format); continue; }

      // Сценарий 2: Вычисляемое динамическое поле
      if (is_callable($format)) { $context[$key] = $format($stream, $context); continue; }

      // Сценарий 3: Вложенная структура
      if (is_array($format)) { $context[$key] = self::processMap($stream, $format, []); continue; }

      // Сценарий 4: Наш красивый бинарный тип (u8, i32b, etc.)
      if (is_string($format))
      {
        if ($config = self::BINARY_FORMATS[$format]?? null)
        {
          // Запрашиваем у переданного стрима точное количество байт
          $rawBytes = $stream->readData($config['size']);
          
          $unpacked = unpack("{$config['mask']}val", $rawBytes);
          $value = $unpacked['val'];
  
          // Корректируем знак и сохраняем в контекст
          $context[$key] = self::resolveSign($format, $value);
          continue;
        }

        throw new \InvalidArgumentException("Неизвестный бинарный формат: '$format'. Используйте u8, i32b, u16l и т.д.");
      }
    }

    return $context;
  }  
  
  /**
   * Восстанавливает отрицательные значения для знаковых типов данных
   */
  private static function resolveSign(string $format, mixed $value): mixed
  {
    if (!str_starts_with($format, 'i')) return $value;
    if (str_contains($format, '16') && $value >=     0x8000)  return $value -     0x10000;
    if (str_contains($format, '32') && $value >= 0x80000000)  return $value - 0x100000000;
    return $value;
  }
  
 /**
   * Возвращает размер в байтах для стандартных форматов PHP unpack()
   */
  private function getBinarySizeByFormat(string $format): int
  {
    return match ((string)$format[0])
    {
      'c', 'C' => 1, // 8-bit signed/unsigned
      's', 'S', 'n', 'v' => 2, // 16-bit
      'i', 'I', 'l', 'L', 'N', 'V', 'f', 'G', 'g' => 4, // 32-bit / float
      'q', 'Q', 'J', 'P', 'd', 'E', 'e' => 8, // 64-bit / double
      default => 0 //throw new \InvalidArgumentException("Неизвестный бинарный формат маски: $format")
    };
  }
  
//****************************************************************
  
  /**
   * Статический метод для рекурсивной упаковки структуры и записи её в стрим
   * @param IStream $stream Стрим, реализующий интерфейс IStream, куда будет производиться запись
   * @param array $formatMap Карта бинарного формата (u8, i32b, etc.)
   * @param array $data Ассоциативный массив с реальными данными для упаковки
   * @return int Общее количество успешно записанных байт
   */
  public static function packStructure(IStream|IWriter $stream, array $formatMap, array $data): int 
  {
    return self::serializeMap($stream, $formatMap, $data);
  }
  
  private static function serializeMap(IStream|IWriter $stream, array $formatMap, array $data): int
  {
    $totalBytesWritten = 0;
  
    foreach ($formatMap as $key => $format)
    {
      // Если ключа нет в данных, но в формате стоит callable - это вычисляемое поле (например, длина)
      if (!array_key_exists($key, $data) && is_callable($format))
      {
        $calculatedValue = $format($data);
        $totalBytesWritten += $stream->writeData((string)$calculatedValue);
        continue;
      }
  
      $value = $data[$key] ?? null;
  
      // Сценарий 1: В формате указано число (int) - пишем сырую строку заданной длины
      if (is_int($format))
      {
        $totalBytesWritten += $stream->writeData(str_pad((string)$value, $format, "\x00"));
        continue;
      }
  
      // Сценарий 2: Вложенная структура (массив в массиве)
      if (is_array($format) && is_array($value))
      {
        $totalBytesWritten += self::serializeMap($stream, $format, $value);
        continue;
      }
  
      // Сценарий 3: Наш красивый бинарный тип (u8, i32b, etc.)
      if (is_string($format))
      {
        if (!isset(self::BINARY_FORMATS[$format]))
          throw new \InvalidArgumentException("Неизвестный бинарный формат для упаковки: '$format'.");
  
        $config = self::BINARY_FORMATS[$format];

        // Корректируем знак перед упаковкой для знаковых типов
        $preparedValue = self::prepareSign($format, $value);

        // Упаковываем данные в бинарную строку
        $packedBytes = pack($config['mask'], $preparedValue);

        // Пишем байты напрямую в синхронный стрим
        $totalBytesWritten += $stream->writeData($packedBytes);
        continue;
      }
  
      // Сценарий 4: Кастомная функция записи, если передана в формате
      if (is_callable($format))
      {
        $result = $format($stream, $value, $data);
        $totalBytesWritten += is_int($result) ? $result : 0;
        continue;
      }
    }
  
    return $totalBytesWritten;
  }
  
  /**
   * Корректирует отрицательные значения для знаковых типов перед передачей в pack()
   */
  private static function prepareSign(string $format, mixed $value): mixed
  {
    if (!is_int($value) || !str_starts_with($format, 'i')) return $value;
    
    if (str_contains($format, '16') && $value < 0) return $value +     0x10000;
    if (str_contains($format, '32') && $value < 0) return $value + 0x100000000;
  }
  
//****************************************************************
}