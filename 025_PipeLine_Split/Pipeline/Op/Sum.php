<?php
/** Оператор суммирования SUM */
class Pipeline_Op_Sum extends Pipeline
{
  /** @var callable */
  private float $total = 0;

  function __construct(Pipeline $parent, ?callable $selector)
  {
    if($selector)
      $parent=$parent->map($selector);
    parent::__construct($parent);
  }

  protected function onProcess(mixed $item): void
  {
    $this->total += $item; 
  }

  protected function onProcessEnd(): void
  {
    // Выдаёт финальную сумму в поток только при завершении
    $this->emit($this->total);
    $this->emitEnd();
  }
}
