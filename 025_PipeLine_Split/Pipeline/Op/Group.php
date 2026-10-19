<?php
/** Оператор группировки по времени и количеству GROUP */
class Pipeline_Op_Group extends Pipeline
{
  private int   $count       ;
  private float $timeout     ;
  private array $chunk       = [];
  private float $lastFlush   ;
  private bool  $timerActive = true;

  function __construct(Pipeline $parent, int $count, float $timeout)
  {
    parent::__construct($parent);
    $this->count     = $count;
    $this->timeout   = $timeout;
    $this->lastFlush = microtime(true);
    $this->scheduleTimer();
  }

  protected function onProcess(mixed $item): void
  {
    $this->chunk[] = $item;
    if (count($this->chunk) >= $this->count)
      $this->flush();
  }

  protected function onProcessEnd(): void
  {
    $this->timerActive = false;
    if (!empty($this->chunk))
      $this->flush(); // Сбрасываем остатки
    
    $this->emitEnd();
  }

  private function flush(): void
  {
    $chunk=$this->chunk;
    if (empty($chunk)) return;
    $this->chunk = [];
    $this->lastFlush = microtime(true);
    $this->emit($chunk);
  }

  private function scheduleTimer(): void
  {
    if (!$this->timerActive || $this->isCancelled || $this->isEnded)
      return;
    
    Loop::delay($this->timeout, $this->onTimerTick(...));
  }

  private function onTimerTick(): void
  {
    if (!$this->timerActive || $this->isCancelled || $this->isEnded) return;

    if (!empty($this->chunk) && (microtime(true) - $this->lastFlush) >= $this->timeout)
      $this->flush();

    $this->scheduleTimer(); // Планируем следующий тик
  }

  private function stopTimer(): void { $this->timerActive = false; }
  protected function onAbort(): void { $this->stopTimer(); }
}
