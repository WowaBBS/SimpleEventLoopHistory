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

  protected function onProcess(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isEnd())
      $this->deferred->resolve(null);
  }

  protected function registerChild(Pipeline $child): void {}
  protected function unregisterChild(Pipeline $child, $reason): void {}
}
