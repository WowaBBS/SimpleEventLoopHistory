<?
abstract class Pipeline_Base extends Pipeline
{
  protected $child { get=>$this->child?->get(); set=>$value? \WeakReference::Create($value):null; }

  /** Безопасное добавление слабой ссылки на ребенка */
  protected function registerChild(Pipeline $child): void
  {
    $old = $this->child;
    if (!$old)
    {
      $this  ->child   = $child ;
      $child ->setParent($this );
      return;
    }
    
    if ($old instanceof Pipeline_Fork)
      $fork = $oldChild;
    else
    {
      $this->child = null;
      $fork = new Pipeline_Fork($this);
      $fork->registerChild($old);
    }
    
    $fork->registerChild($child);
  }

  /** Родитель сам управляет списком детей и решает, тушить ли себя */
  function unregisterChild(Pipeline $child, $reason): void
  {
    if ($this->child !== $child) return; //TODO: logicError
    $this->child = null;
    
    $this->childLock(false);
    
    if ($this->isProtected)
    {
      $this->registerChild(Pipeline_Final_Null::get());
      return;
    }
    
    // КРИТИЧЕСКОЕ ПРАВИЛО: Родитель отменяется только если у него
    // НЕ ОСТАЛОСЬ других активных дочерних потребителей!
    $this->onNoChild($reason);
  }
    
  protected final function onProcess(Pipeline_Event $type, mixed $payload): void
  {
  //Logger::debug('onProcess: ',$type, ': ', $payload);
    $child = $this->child;

    // Если ребенок умер или отменился, прекращаем проталкивание данных
    if (!$child) { $this->abortPipeline(); return; }

    // Если пришла ошибка, а текущий оператор - не Catch, транзитом шлем ошибку ребенку
    if ($type->isError() && !($this instanceof Pipeline_Op_Catch)) //TODO: Catch workaround
    {
      $child->process($type, $payload);
      return;
    }

    // Вызываем конкретную реализацию оператора, передавая туда живой объект ребенка
    $this->onProcessWithChild($type, $payload, $child);
  }

  /**
   * Новый сигнатурный контракт для всех промежуточных операторов
   */
  protected abstract function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void;

//****************************************************************
  function canEmit   (): bool { return $this->child?->isValid()?? false; }
  function emit      ($data  ) { $this->child?->process(Pipeline_Event::DATA  ,$data  ); }
  function emitError ($error ) { $this->child?->process(Pipeline_Event::ERROR ,$error ); }
  function emitEnd   (       ) { $this->child?->process(Pipeline_Event::END   , null  ); }
//****************************************************************
// Debug function

  protected function buildDebugName(array &$res, $child=null)
  {
    parent::buildDebugName($res, $child);

    if(!$child) return;
    
    if($child===$this->child)
      $res[]='.';
    else
      $res[]='?.';
  }
//****************************************************************
}