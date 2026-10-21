<?php
/** Конечная точка FINISH (Блокирует файбер до конца) */
class Pipeline_Final_Finish extends Pipeline_Final_Base
{
  protected function onProcessFinalEvent(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isEnd())
      $this->deferred->resolve();
  }
}
