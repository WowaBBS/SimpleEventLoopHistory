<?php

class Pipeline_Final_First extends Pipeline_Final_Base
{
  protected function onProcessFinalEvent(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isData())
    {
      $this->deferred->resolve($payload);
      $this->abortPipeline();
      return;
    }
    
    if ($type->isEnd()) // «авершаетс€ строго по событию конца потока
      $this->deferred->reject(new \UnderflowException('The pipeline finishes without data'));
  }
}
