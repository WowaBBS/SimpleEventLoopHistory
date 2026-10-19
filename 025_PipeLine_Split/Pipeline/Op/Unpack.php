<?php
/** Оператор одноуровневой развёртки UNPACK */
class Pipeline_Op_Unpack extends Pipeline
{
  private int $concurrency;
  private int $activeCount = 0;

  public function __construct(Pipeline $parent, int $concurrency = 1)
  {
    parent::__construct($parent);
    $this->concurrency = $concurrency;
  }

  protected function onProcess(mixed $item): void
  {
    // Сценарий 1: Элемент - нативный PHP-генератор и включен режим параллелизма
    if ($item instanceof \Generator && $this->concurrency > 1)
    {
      // Если пул генераторов переполнен, засыпаем и ждем освобождения слота
      while ($this->activeCount >= $this->concurrency)
        sleepAsync(0.005)->await(); //TODO: Make event based solution

      $this->activeCount++;

      // Запускаем выкачивание дочернего генератора в отдельном независимом файбере
      Loop::async($this->consumeGenerator(...), $item);
      return;
    }

    // Сценарий 2: Элемент - обычный массив или Traversable объект
    if (!is_iterable($item))
      throw new \InvalidArgumentException("The unpack operator expects iterable data (array|\Traversable|\Generator)");

    // Сценарий 3: Обычный генератор при concurrency = 1 (последовательный запуск внутри текущего файбера)
    $this->consumeIterable($item);
  }

  /**
   * Асинхронное параллельное чтение генератора в изолированном файбере
   */
  private function consumeGenerator(\Generator $generator): void
  {
    try
    {
      $this->consumeIterable($generator);
    }
    catch (\Throwable $e)
    { // Если внутри дочернего генератора что-то сломалось - перенаправляем в поток ошибок
      $this->emitError($e);
    //$this->processError($e);
    }
    finally
    {
      $this->activeCount--;
    }
  }

  /**
   * Последовательное чтение генератора (для читаемого стек-трейса при concurrency = 1)
   */
  private function consumeIterable(iterable $list): void
  {
    foreach ($list as $item)
      if ($this->isCancelled || $this->isEnded)
        return;
      else
        $this->emit($item); // Пушим элемент в успешный поток данных
      //sleepAsync(0)->await(); // Даем переключить контекст другим генераторам в пуле
  }
}