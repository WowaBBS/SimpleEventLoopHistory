<?php
class Pipeline_Op_Filter extends Pipeline_Base
{
  private $predicate;

  public function __construct(Pipeline $parent, callable $predicate)
  {
    parent::__construct($parent);
    $this->predicate = $predicate;
  }

  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
    if ($type->isData() && !($this->predicate)($payload)) return;
    
    $child->process($type, $payload);
  }
  
}