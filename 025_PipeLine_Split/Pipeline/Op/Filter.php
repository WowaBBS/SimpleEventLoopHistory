<?php
class Pipeline_Op_Filter extends Pipeline
{
  private $predicate;

  public function __construct(Pipeline $parent, callable $predicate)
  {
    parent::__construct($parent);
    $this->predicate = $predicate;
  }

  protected function onProcess(mixed $item): void
  {
    if (($this->predicate)($item))
      $this->emit($item);
  }
}