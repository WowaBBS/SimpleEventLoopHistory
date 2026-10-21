<?php
/** Конечная точка Last (Возвращает Promise) */
class Pipeline_Final_Last extends Pipeline_Final_Base
{
  protected mixed $result    = null  ;
  protected bool  $hasResult = false ;
  
  protected function onProcessFinalEvent(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isData())
    {
      $this->result = $payload;
      $this->hasResult = true;
      return;
    }
    
    if ($type->isEnd()) // Завершается строго по событию конца потока
      if ($this->hasResult )
        $this->deferred->resolve($this->result);
      else
        $this->deferred->reject(new \UnderflowException('The pipeline finishes without data'));
  }
}
