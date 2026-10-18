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

  protected function onProcess(mixed $item): void
  {
    // Если прилетел обычный успешный элемент, просто шлем его дальше
    if (!($item instanceof Pipeline_Error_Box))
    {
      $this->emit($item);
      return;
    }

    // Если прилетела коробка с ошибкой - вскрываем!
    try
    {
      $exception = $item->getException();
      $fallback = ($this->errorHandler)($exception);

      // Если обработчик вернул какое-то дефолтное значение-заглушку,
      // пускаем её вместо ошибки дальше по трубе
      if ($fallback !== null)
        $this->emit($fallback);
    }
    catch (\Throwable $fatal)
    { // Если сам обработчик ошибок упал, пускаем новую ошибку вниз
      $this->emit(new Pipeline_Error_Box($fatal));
    }
  }
}