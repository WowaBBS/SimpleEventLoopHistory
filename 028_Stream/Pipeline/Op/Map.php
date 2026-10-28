<?php
/** Оператор трансформации MAP */
class Pipeline_Op_Map extends Pipeline_Base
{
  /** @var callable */
  private $callback;

  function __construct(Pipeline $parent, callable $callback)
  {
    $this->callback = $callback;

    parent::__construct($parent);
  }

  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
    if (!$type->isData()) { $child->process($type, $payload); return; }

    // Обработка данных DATA
    try
    {
      $result = ($this->callback)($payload);
    //if ($result instanceof Promise)
    //    $result = $result->await();
    }
    catch(\Throwable $e)
    {
      $this->emitError($e);
      return;
    }
    
    // Проталкиваем данные напрямую в метод ребенка!
    $this->emit($result);
  }
}
