<?php
class Pipeline_Op_Catch extends Pipeline_Base
{
  /** @var callable */
  private $errorHandler;

  public function __construct(Pipeline $parent, callable $errorHandler)
  {
    parent::__construct($parent);
    $this->errorHandler = $errorHandler;
  }

  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
    if (!$type->isError()) { $child->process($type, $payload); return; }

    // Перехват ERROR
    $fallback = ($this->errorHandler)($payload);
    // Если обработчик вернул альтернативное значение (заглушку),
    // мы возвращаем его в поток УСПЕШНЫХ данных через emit()!
    if ($fallback !== null)
      $child->emit($fallback); // Возвращаем ошибку в русло успешных данных дочернего элемента

    // Если обработчик вернул null, значит ошибка просто поглощена (залогирована),
    // и мы никуда ничего не шлем для этого элемента.
  }
}