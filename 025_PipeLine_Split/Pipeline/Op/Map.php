<?php
/** Оператор трансформации MAP */
class Pipeline_Op_Map extends Pipeline
{
  /** @var callable */
  private $callback;

  function __construct(Pipeline $parent, callable $callback)
  {
    parent::__construct($parent);
    $this->callback = $callback;
  }

  protected function onProcess(mixed $item): void
  {
    $result = ($this->callback)($item);
    
    $this->emit($result);
  }
}
