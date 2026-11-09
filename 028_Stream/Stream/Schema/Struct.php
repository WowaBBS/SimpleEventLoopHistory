<?php
class Stream_Schema_Struct extends Stream_Schema_Node
{
  private array $fields=[];
  
  public function __construct(array $fields)
  {
    foreach ($fields as $key => &$node)
      $node = Stream_Schema_Factory::get($node);
    
    $this->fields = $fields;
  }
  
  public function read(IStream $stream, mixed &$value, $context): void
  {
    if (!is_array($value) && !($value instanceof ArrayAccess))
      $value = [];
    foreach ($this->fields as $key => $node)
      $node->read($stream, $value[$key], $value);
  }

  public function write(IStream $stream, mixed $value, $context): int
  {
    if (!is_array($value))
      throw new \InvalidArgumentException('Данные для Stream_Schema_Struct должны быть массивом.');
    
    $totalBytes = 0;
    foreach ($this->fields as $key => $node)
      $totalBytes += $node->write($stream, $value[$key] ?? null, $value);
    
    return $totalBytes;
  }
}
