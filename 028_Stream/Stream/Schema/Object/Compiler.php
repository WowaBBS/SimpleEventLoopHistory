<?
class Stream_Schema_Object_Compiler
{
  private static array $structCache = [];

  /**
   * Генерирует или достает из кэша Stream_Schema_Struct для конкретного класса DTO
   */
  public static function compile(string $className): Stream_Schema_Struct
  {
    if (isset(self::$structCache[$className]))
      return self::$structCache[$className];

    if (!class_exists($className))
      throw new \InvalidArgumentException("Класс {$className} не найден для компиляции схемы.");

    $reflection = new \ReflectionClass($className);
    $map = [];

    foreach ($reflection->getProperties() as $property)
    {
      $attributes = $property->getAttributes(Stream_Schema_Object_Field::class);
      if (empty($attributes))
        continue; // Игнорируем свойства без атрибута #[Field]

      /** @var Field $fieldAttr */
      $fieldAttr = $attributes[0]->newInstance();
      $propertyName = $property->getName();

      // Сценарий 1: Задан префикс длины (например, для строк p:u16b)
      if ($fieldAttr->prefixed !== null)
      {
        $map[$propertyName] = Stream_Schema_Factory::prefixed($fieldAttr->prefixed);
        continue;
      }

      // Сценарий 2: Если тип - это имя другого класса, значит это вложенная структура!
      if (is_string($fieldAttr->type) && class_exists($fieldAttr->type))
      {
        $map[$propertyName] = self::compile($fieldAttr->type);
        continue;
      }

      // Сценарий 3: Передан стандартный тип фабрики (строка, инт фиксированной длины или IStream_Codec)
      $map[$propertyName] = Stream_Schema_Factory::get($fieldAttr->type);
    }

    if (empty($map))
      throw new \LogicException("Класс {$className} не содержит свойств с атрибутом #[Field].");

    return self::$structCache[$className] = Stream_Schema_Factory::struct($map);
  }
}
