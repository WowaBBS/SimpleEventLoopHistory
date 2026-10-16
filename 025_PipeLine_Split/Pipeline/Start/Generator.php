<?
class Pipeline_Start_Generator extends Pipeline
{
  private \Generator $generator;
  private bool $isStarted = false;

  function __construct(\Generator $generator)
  {
    parent::__construct(null);
    $this->generator = $generator;
  }

  function startIteration(): void
  {
    if ($this->isStarted) return;
    
    $this->isStarted = true;

    Loop::async($this->runLoopIteration(...));
  }

  private function runLoopIteration(): void
  {
    try
    {
      while ($this->generator->valid())
      {
        if ($this->isCancelled || empty($this->children)) return;

        $item = $this->rootGeneratorItemFetch();

        $this->emit($item);
        
        sleepAsync(0)->await();
      }
      $this->emitEnd();
    }
    catch (\Throwable $e)
    {
      Logger::Exception($e, 'Pipeline.runLootIteration');
      $this->abortPipeline();
    }
  }
  
  /** ¬ынесено в метод дл€ читаемости стек-трейса */
  private function rootGeneratorItemFetch(): mixed
  {
    $item = $this->generator->current();
    $this->generator->next();
    return $item;
  }
}
