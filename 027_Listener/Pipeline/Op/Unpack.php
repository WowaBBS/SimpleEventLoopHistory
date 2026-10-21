<?php
/** Оператор одноуровневой развёртки UNPACK */
class Pipeline_Op_Unpack extends Pipeline_Base
{
  private int $concurrency;
  private int $activeCount = 0;

  public function __construct(Pipeline $parent, int $concurrency = 1)
  {
    $this->concurrency = $concurrency;
    
    parent::__construct($parent);
  }

  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
    if ($type->isData())
    {
      // Сценарий 2: Элемент - обычный массив или Traversable объект
      if (!is_iterable($payload))
        throw new \InvalidArgumentException('The unpack operator expects iterable data (array|\Traversable|\Generator)');
  
      // Сценарий 1: Элемент - нативный PHP-генератор и включен режим параллелизма
      if ($payload instanceof \Traversable && $this->concurrency > 1)
      {
        $this->activeCount++;
  
        // Если пул генераторов переполнен, засыпаем и ждем освобождения слота
        if ($this->activeCount >= $this->concurrency)
          $this->setOverloaded(true);
  
        // Запускаем выкачивание дочернего генератора в отдельном независимом файбере
        Loop::async(fn()=>$this->consumeIterable($payload));
        return;
      }
  
      // Сценарий 3: Обычный генератор при concurrency = 1 (последовательный запуск внутри текущего файбера)
      $this->consumeIterable($payload);
      return;
    }
    if ($type->isEnd())
    {
      while ($this->activeCount > 0)
        sleepAsync(0.001)->await();
    }
    $child->process($type, $payload);
  }

  /**
   * Последовательное чтение
   */
  private function consumeIterable(iterable $list): void
  {
    try
    {
      foreach ($list as $item)
        if ($this->canEmit())
          $this->emit($item); // Пушим элемент в успешный поток данных
        else
          return;
        //sleepAsync(0)->await(); // Даем переключить контекст другим генераторам в пуле
    }
    catch (\Throwable $e)
    { // Если внутри дочернего генератора что-то сломалось - перенаправляем в поток ошибок
      $this->emitError($e);
    }
    finally
    {
      $counter=$this->activeCount--;
      if ($counter === $this->concurrency)
        $this->setOverloaded(false);
    }
  }
}