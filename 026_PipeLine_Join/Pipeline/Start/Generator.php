<?
class Pipeline_Start_Generator extends Pipeline_Base
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
        if (!$this->isValid()) return;

        $item = $this->generator->current();
        $this->generator->next();

      //Logger::debug('Iterate: ', $item);
        $this->emit($item);
        
      //
      sleepAsync(0)->await();
      }
      
      if (!$this->isValid()) return;
      
      $this->emitEnd();
    }
    catch (\Throwable $e)
    {
      Logger::Exception($e, 'Pipeline.runLootIteration');
      $this->abortPipeline();
    }
  }
  
  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
  //Logger::debug('onProcessWC: ',$type, ': ', $payload);
    $child->process($type, $payload);
  }
}
