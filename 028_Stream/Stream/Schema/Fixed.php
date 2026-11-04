<?php
class Stream_Schema_Fixed extends Stream_Schema_Node
{
  private int $size = 0;
  
  public function __construct(int $size)
  {
    $this->size = $size;
  }
  
  public function read(IStream $stream, array $context =[]): array
  {
    return $node->read($this->size);
  }

  public function write(IStream $stream, mixed $value, array $context =[]): int
  {
    if (!is_string($value))
      throw new \InvalidArgumentException("Данные для Stream_Schema_Size должны быть string.");
      
    if (strlen($value) !== $this->size)
      throw new \InvalidArgumentException("Данные для Stream_Schema_Size должны быть size={$this->size}.");
    
    return $stream->write($value);
  }
}
