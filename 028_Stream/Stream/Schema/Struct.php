<?php
class Stream_Schema_Struct extends Stream_Schema_Node
{
  private array $fields=[];
  
  public function __construct(array $fields)
  {
    foreach ($fields as $key => &$node)
      $node = Stream_Schema_Context::normalize($node);
    
    $this->fields = $fields;
  }
  
  public function read(IStream $stream, array $context =[]): array
  {
    $result = [];
    foreach ($this->fields as $key => $node)
      $result[$key] = $node->read($stream, $result);
    
    return $result;
  }

  public function write(IStream $stream, mixed $value, array $context =[]): int
  {
    if (!is_array($value))
      throw new \InvalidArgumentException("Данные для Stream_Schema_Struct должны быть массивом.");
    
    $totalBytes = 0;
    foreach ($this->fields as $key => $node)
      $totalBytes += $node->write($stream, $value[$key] ?? null, $value);
    
    return $totalBytes;
  }
}
