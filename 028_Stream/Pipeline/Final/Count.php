<?
class Pipeline_Final_Count extends Pipeline_Final_Base
{
  /** @var callable */
  private int $count  = 0 ;

  public function __construct(Pipeline $parent, int $startWith = 0)
  {
    $this->count = $startWith;
    
    parent::__construct($parent);
  }


  protected function onProcessFinalEvent(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isData()) { $this->count++; return; }
    if ($type->isEnd()) $this->deferred->resolve($this->count);
  }
}