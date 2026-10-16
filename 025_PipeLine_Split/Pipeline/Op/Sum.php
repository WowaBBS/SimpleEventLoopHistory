<?php
/** Оператор суммирования SUM */
class Pipeline_Op_Sum extends Pipeline
{
  /** @var callable */
  private $selector;
  private float $total = 0;

  function __construct(Pipeline $parent, callable $selector)
  {
    parent::__construct($parent);
    $this->selector = $selector;
  }

  protected function onProcess(mixed $item): void
  {
    $this->total += ($this->selector)($item);
  }

  protected function onProcessEnd(): void
  {
    // Выдаёт финальную сумму в поток только при завершении
    $this->emit($this->total);
    $this->emitEnd();
  }
}
