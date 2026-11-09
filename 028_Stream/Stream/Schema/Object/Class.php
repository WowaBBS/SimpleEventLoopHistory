<?php
class Stream_Schema_Object_Class extends Stream_Schema_Node
{

  private static array $cache = [];

  public static function create(string $className): Stream_Schema_Object_Class
  {
    return self::$cache[$className] ??= new static($className);
  }

  private string               $class  ;
  private Stream_Schema_Struct $struct ;
  
  public function __construct(
    string $class,
  )
  {
    $this->class  = $class  ;
  //$this->struct = $struct ;
    $this->compile();
  }
  
  /**
   * Генерирует Stream_Schema_Struct для конкретного класса DTO
   */                                             
  protected function compile()
  {
    if (!class_exists($this->class))
      throw new \InvalidArgumentException("Класс {$className} не найден для компиляции схемы.");

    $reflection = new \ReflectionClass($this->class);
    $map = [];

    foreach ($reflection->getProperties() as $property)
    {
      $attributes = $property->getAttributes(Stream_Schema_Object_Attr_Field::class);
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
        $map[$propertyName] = self::create($fieldAttr->type);
        continue;
      }

      // Сценарий 3: Передан стандартный тип фабрики (строка, инт фиксированной длины или IStream_Codec)
      $map[$propertyName] = Stream_Schema_Factory::get($fieldAttr->type);
    }

    if (empty($map))
      throw new \LogicException("Класс {$className} не содержит свойств с атрибутом #[Field].");
      
    $this->struct = Stream_Schema_Factory::struct($map);
  }
  
  public function read(IStream $stream, mixed &$value, $context): void
  {
    // Автоматическая гидратация объекта
    if (!($value instanceof $this->class))
      $value = new $this->class(); //TODO: Custom creation attribute
    
    $record = []; //TODO? (array)$value?
    $this->struct->read($stream, $record, $context);

    foreach ($record as $key => $v)
      // Если вложенный элемент тоже является структурой, ObjectCompiler
      // вернет массив, его можно рекурсивно гидрировать (для простоты заполняем свойства напрямую)
      $value->{$key} = $v; //TODO: Reflexion
  }

  public function write(IStream $stream, mixed $value, $context): int
  {
    // Превращаем объект в массив его публичных/защищенных свойств
    $data = (array)$value;
    // Убираем маркеры области видимости из ключей (костыли преобразования (array)$obj в PHP)
    $cleanData = [];
    foreach ($data as $key => $value)
    {
      $cleanKey = str_contains($key, "\x00") ? substr($key, strrpos($key, "\x00") + 1) : $key;
      $cleanData[$cleanKey] = $value;
    }
    
    $value = $cleanData;
    
    return $this->struct->write($stream, $value, $context);
  }
}
