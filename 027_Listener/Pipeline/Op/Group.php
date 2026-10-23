<?php
/** Оператор группировки по времени и количеству GROUP */
class Pipeline_Op_Group extends Pipeline_Base
{
  private int   $count       ;
  private float $timeout     ;
  private array $chunk       = [];
  private float $lastFlush   ;
  private bool  $timerActive = true;

  function __construct(Pipeline $parent, int $count, float $timeout)
  {
    $this->count     = $count;
    $this->timeout   = $timeout;
    $this->lastFlush = Loop::getTime();
    
    parent::__construct($parent);

    $this->scheduleTimer();
  }

  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
    if ($type->isData())
    {
      $this->chunk[] = $payload;
      if (count($this->chunk) >= $this->count)
        $this->flush();
      return;
    }

    if ($type->isEnd())
    {
      $this->timerActive = false;
      if (!empty($this->chunk))
        $this->flush(); // Сбрасываем остатки
      
      $this->emitEnd();
      return;
    }
    
    $child->process($type, $payload);
  }
  
  private function flush(): void
  {
    $chunk=$this->chunk;
    if (empty($chunk)) return;
    $this->chunk = [];
    $this->lastFlush = Loop::getTime();
    $this->emit($chunk);
  }

  private function scheduleTimer(): void
  {
    if (!$this->timerActive || !$this->isValid())
      return;
    
    Loop::delay($this->timeout, $this->onTimerTick(...));
  }

  private function onTimerTick(): void
  {
    if (!$this->timerActive || !$this->isValid()) return;

    if (!empty($this->chunk) && (Loop::getTime() - $this->lastFlush) >= $this->timeout)
      $this->flush();

    $this->scheduleTimer(); // Планируем следующий тик
  }

  private function stopTimer(): void { $this->timerActive = false; }
  protected function onAbort(): void { $this->stopTimer(); }
}
