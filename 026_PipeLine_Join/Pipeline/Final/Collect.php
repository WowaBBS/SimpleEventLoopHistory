<?php
/** Конечная точка COLLECT (Возвращает Promise) */
class Pipeline_Final_Collect extends Pipeline
{
  private Deferred $deferred; // Ссылка на финальный Deferred
  private mixed $result = null;

  function __construct(Pipeline $parent)
  {
    parent::__construct($parent);
    $this->deferred = new Deferred();
    $this->deferred->promise()->setOnCancel($this->abortPipeline(...));

    // Запускаем итерацию в корне
    $this->findRoot()->startIteration();
  }

  // Защищает эндпоинт от ложного авто-удаления
  protected function isEndpoint(): bool { return true; }
  
  function getPromise(): Promise { return $this->deferred->promise(); }

  // Получает данные ИЗ ЕМИТА предыдущего пайплайна, сохраняя стейт
  protected function onProcess(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isData())
      $this->result = $payload;
    elseif ($type->isError())
    {
     // Если ошибка дотекла до самого конца пайплайна и её никто не перехватил,
     // мы отклоняем (reject) результирующий Promise.
     
      $this->deferred->reject($payload);
      $this->abortPipeline(); // Гасим пайплайн
    }
    elseif ($type->isEnd()) // Завершается строго по событию конца потока
      $this->deferred->resolve($this->result);
    //$this->isEnded = true;
  }
  
  protected function registerChild(Pipeline $child): void {}
  protected function unregisterChild(Pipeline $child, $reason): void {}
}
