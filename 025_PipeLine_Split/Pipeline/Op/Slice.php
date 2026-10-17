<?
class Pipeline_Op_Slice extends Pipeline 
{
  private  int $skip  ;
  private ?int $limit ;

  public function __construct(Pipeline $parent, int $skip = 0, ?int $limit = null) 
  {
    parent::__construct($parent);
    $this->skip  = $skip  ;
    $this->limit = $limit ;
  }

  /** Геттеры и сеттеры для пересчета на лету */
  public function getSkip  ():  int { return $this->skip  ; }
  public function getLimit (): ?int { return $this->limit ; }

  public function updateSlice(int $skip, ?int $limit): void
  {
    if ($skip)
      $this->skip+= $skip;
    if ($this->limit !== null)
      $this->limit-= $skip;
    if ($limit !== null)
      if ($this->limit)
        $this->limit = min($this->limit, $limit);
      else
        $this->limit = $limit;
  }

  protected function onProcess(mixed $item): void
  {
    // Пропускаем первые $skip элементов
    if ($this->skip>0)
    {
      $this->skip--;
      return;
    }
    
    if ($this->limit === null) return;
    
    // Если задан лимит и мы его достигли
    if ($this->limit > 0)
    {
      $limit=--$this->limit;
      $this->emit($item);
      if($limit > 0) return;
    }
    
    $this->abortPipeline('limit');
  }
  
  function slice(int $skip=0, ?int $limit=null): self { $this->updateSlice($skip, $limit); return $this; }
}