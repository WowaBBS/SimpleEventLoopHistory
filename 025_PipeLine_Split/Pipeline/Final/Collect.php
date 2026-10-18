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
  protected function onProcess(mixed $item): void { $this->result = $item; }

  /**
   * Если ошибка дотекла до самого конца пайплайна и её никто не перехватил,
   * мы отклоняем (reject) результирующий Promise.
   */
  protected function onProcessError(\Throwable $exception): void
  {
    $this->deferred->reject($exception);
    $this->abortPipeline(); // Гасим пайплайн
  }
  
  // Завершается строго по событию конца потока
  protected function onProcessEnd(): void { $this->deferred->resolve($this->result); }
}
