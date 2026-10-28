<?
class Pipeline_Final_Average extends Pipeline_Final_Base
{
  /** @var callable */
  private mixed $count  = 0 ;
  private mixed $sum    = 0 ;

  public function __construct(Pipeline $parent, int $count = 0, int $sum = 0)
  {
    $this->count = $count ;
    $this->sum   = $sum   ;
    
    parent::__construct($parent);
  }

  protected function onProcessFinalEvent(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isData()) { $this->count++; $this->sum+=$payload; return; }
    if ($type->isEnd())
      if ($this->count)
        $this->deferred->resolve($this->sum / $this->count);
      else
        $this->deferred->reject(new \UnderflowException('The pipeline finishes without data'));
  }
}