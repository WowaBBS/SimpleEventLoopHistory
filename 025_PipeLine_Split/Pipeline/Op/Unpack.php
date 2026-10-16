<?php
/** Оператор одноуровневой развёртки UNPACK */
class Pipeline_Op_Unpack extends Pipeline
{
  protected function onProcess(mixed $item): void
  {
    if (!is_iterable($item))
      throw new \InvalidArgumentException("The unpack operator expects iterable data (array|\Traversable)");
    
    foreach ($item as $subItem)
      $this->emit($subItem);
  }
}
