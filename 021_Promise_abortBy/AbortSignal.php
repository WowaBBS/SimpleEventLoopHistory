<?
/**
 * Токен отмены, передаваемый внутрь асинхронных функций или шлёт событие отмены промисам.
 */
class AbortSignal
{
  private bool $aborted = false;
  private ?string $reason = null;
  private array $listeners = [];
  private array $associatedPromises = []; // Храним связанные промисы

  function isAborted(): bool { return $this->aborted; }
  function getReason(): ?string { return $this->reason; }

  /**
   * Позволяет старой асинхронной функции подписаться на событие отмены
   */
  function onAbort(callable $listener): void
  {
    if ($this->aborted)
    {
      $listener($this->reason);
      return;
    }
    $this->listeners[] = $listener;
  }

  /**
   * Привязывает промис к этому сигналу.
   */
  function follow(Promise $promise): void
  {
    if ($this->aborted)
    {
      $promise->abort($this->reason);
      return;
    }
    $this->associatedPromises[] = $promise;
  }

  /**
   * Внутренний метод, вызываемый только из AbortController
   */
  function triggerAbort(?string $reason = null): void
  {
    if ($this->aborted) return;
    $this->aborted = true;
    $this->reason = $reason ?? "Операция была отменена.";

    // 1. Оповещаем обычных слушателей
    foreach ($this->listeners as $listener)
      $listener($this->reason);
    $this->listeners = [];

    // 2. Раздаем сигнал отмены всем привязанным промисам!
    foreach ($this->associatedPromises as $promise)
        $promise->abort($this->reason);
    
    $this->associatedPromises = [];
  }
}