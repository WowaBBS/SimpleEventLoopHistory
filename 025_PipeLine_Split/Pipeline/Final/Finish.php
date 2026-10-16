<?php
/** Конечная точка FINISH (Блокирует файбер до конца) */
class Pipeline_Final_Finish extends Pipeline
{
  private Deferred $deferred;

  function __construct(Pipeline $parent)
  {
    parent::__construct($parent);
    $this->deferred = new Deferred();
    $this->findRoot()->startIteration();
  }

  protected function isEndpoint(): bool { return true; }

  function wait(): void { $this->deferred->promise()->await(); }

  protected function onProcessEnd(): void
  {
    $this->deferred->resolve(null);
    $this->emitEnd();
  }
}
