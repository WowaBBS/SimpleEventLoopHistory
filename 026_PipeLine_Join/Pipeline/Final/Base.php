<?
abstract class Pipeline_Final_Base extends Pipeline
{
  protected Deferred $deferred;

  function __construct(Pipeline $parent)
  {
    $this->deferred = new Deferred();
    $this->deferred->promise()->onReject($this->abortPipeline(...), $this);

    parent::__construct($parent);
    
    // Запускаем итерацию в корне
    $this->findRoot()->startIteration();
  }

  // Защищает эндпоинт от ложного авто-удаления
  protected final function isEndpoint(): bool { return true; }

  function getPromise(): Promise { return $this->deferred->promise(); }
  function wait(): void { $this->deferred->promise()->await(); }

  /**
   * Все финальные операторы одинаково реагируют на ошибки и отмену
   */
  protected function onProcess(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isError())
    {
      $this->deferred->reject($payload);
      $this->abortPipeline();
      return;
    }

    $this->onProcessFinalEvent($type, $payload);
  }

  /**
   * Кастомная логика сборщика (DATA и END)
   */
  protected abstract function onProcessFinalEvent(Pipeline_Event $type, mixed $payload): void;

  protected function registerChild(Pipeline $child): void {}
  protected function unregisterChild(Pipeline $child, $reason): void {}
}