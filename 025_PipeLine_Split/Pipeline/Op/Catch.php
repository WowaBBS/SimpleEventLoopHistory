<?php
class Pipeline_Op_Catch extends Pipeline
{
  /** @var callable */
  private $errorHandler;

  public function __construct(Pipeline $parent, callable $errorHandler)
  {
    parent::__construct($parent);
    $this->errorHandler = $errorHandler;
  }

  protected function onProcessError(\Throwable $exception): void
  {
    $fallback = ($this->errorHandler)($exception);

    // Если обработчик вернул альтернативное значение (заглушку),
    // мы возвращаем его в поток УСПЕШНЫХ данных через emit()!
    if ($fallback !== null)
      $this->emit($fallback);

    // Если обработчик вернул null, значит ошибка просто поглощена (залогирована),
    // и мы никуда ничего не шлем для этого элемента.
  }
}