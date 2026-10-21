<?php
/** Оператор суммирования SUM */
class Pipeline_Op_Sum extends Pipeline_Base
{
  /** @var callable */
  private float $total = 0;

  function __construct(Pipeline $parent, ?callable $selector)
  {
    if($selector)
      $parent=$parent->map($selector);
    parent::__construct($parent);
  }

  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
    if ($type->isEnd())
    { // Выдаёт финальную сумму в поток только при завершении
      $this->emit($this->total);
      $this->emitEnd();
      return;
    }

    if ($type->isData())
      $this->total += $payload;
  }
}
