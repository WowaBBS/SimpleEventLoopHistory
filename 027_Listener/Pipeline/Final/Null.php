<?php

class Pipeline_Final_Null extends Pipeline
{
  static function get() { static $result=new self(); return $result; }

  function __construct() { parent::__construct(null); }
  
  protected function setParent(Pipeline $parent):void {}
  
  function abortPipeline($reason='abort'): void {}

  protected function isEndpoint(): bool { return true; }
  
  // Получает данные ИЗ ЕМИТА предыдущего пайплайна, сохраняя стейт
  protected function onProcess(Pipeline_Event $type, mixed $payload): void { $this->isEnded = false; } //TODO: Workaround
  protected function registerChild(Pipeline $child): void {}
  protected function unregisterChild(Pipeline $child, $reason): void {}
}
