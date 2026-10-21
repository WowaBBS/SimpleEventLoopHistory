<?
class Pipeline_Final_Reduce extends Pipeline_Final_Base
{
  /** @var callable */
  private mixed $result  = null  ;
  private       $accumulator     ;
  private mixed $defaultValue    ;
  private bool  $hasDefaultValue ;
  private bool  $hasData = false ;

  public function __construct(
    Pipeline  $parent          ,
    callable  $accumulator     ,
    mixed     $defaultValue    = null,
    ?bool     $hasDefaultValue = null,
    ?callable $selector        = null,
  )
  {
    if($selector)
      $parent=$parent->map($selector);
    
    $hasDefaultValue ??= !is_null($defaultValue);
  
    $this->accumulator     = $accumulator     ;
    $this->defaultValue    = $defaultValue    ;
    $this->hasDefaultValue = $hasDefaultValue ;

    if ($hasDefaultValue || $defaultValue !== null)
      $this->result = $this->defaultValue;
    
    parent::__construct($parent);
  }

  protected function onProcessFinalEvent(Pipeline_Event $type, mixed $payload): void
  {
    if ($type->isData())
    {
      $this->hasData = true;

      if (!$this->hasDefaultValue)
      {
        // Если начального значения не было, первый пришедший элемент становится базой
        $this->result = $payload;
        $this->hasDefaultValue = true;
      }
      else
        // Иначе применяем операцию свертки (сложение, макс, мин и т.д.)
        $this->result = ($this->accumulator)($this->result, $payload);
    
      return;
    }

    if ($type->isEnd())
    {
      // Если данных в потоке не было и дефолтное значение не задано - кидаем эксепшн
      if (!$this->hasData && !$this->hasDefaultValue)
        $this->deferred->reject(new \UnderflowException('The pipeline finishes without data; there is no default value.'));
      else
        $this->deferred->resolve($this->result);
    }
  }
}