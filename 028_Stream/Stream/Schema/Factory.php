<?php

class Stream_Schema_Factory
{
  static array $cache = [];

  static function get($node)
  {
    if ($node instanceof IStream_Codec) return $node;

    // Сценарий 1: Число - читаем фиксированную строку байт
    if (is_int    ($node)) return self::fixed($node);
    
    // Сценарий 2: Вычисляемое динамическое поле
    //TODO: ?if (is_callable($node)) return self::func($node);
    
    // Сценарий 3: Вложенная структура
    if (is_array  ($node)) return self::struct($node);

    // Сценарий 4: Наш красивый бинарный тип (uint8, int32be, etc.)
    if (is_string ($node)) return self::named($node);

    throw new \LogicException("Поле '{$node}' должно реализовывать интерфейс IStream_Codec");
  }
  
  static function fixed    (int    $size   ) { return self::$cache[$size] ??= new Stream_Schema_Fixed($size); }
  static function struct   (array  $map    ) { return new Stream_Schema_Struct        ($map    ); }
  static function prefixed (string $lenFmt ) { return     Stream_Schema_Prefixed ::get($lenFmt ); }
//static function type     (string $fmt    ) { return     Stream_Schema_Type     ::get($fmt    ); }
  
  static function named(string $name)
  {
    return self::$cache[$name] ??= 
        Stream_Schema_Int_Factory ::try($name)
      //Stream_Schema_Type        ::try($name)
      ??Stream_Schema_Prefixed    ::try($name)
      ??throw new \LogicException("Неизвестный бинарный формат: '$name'");
  }
  
  static function schema(IStream $stream, IStream_Codec|string|IStream_Schema_Object $schema): Stream_Schema_Context
  {
    if ($schema instanceof IStream_Schema_Object) $schema = $schema::class;
    if (is_string($schema)) $schema = Stream_Schema_Object_Class::create($schema);
    if ($schema instanceof IStream_Codec) return new Stream_Schema_Context($stream, $schema);
    TODO::Exception();
  }
}
