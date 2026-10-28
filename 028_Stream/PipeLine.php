<?
//Fluent API
abstract class Pipeline
{
  protected bool $isCancelled = false; // Был ли этот конкретный шаг отменен
  protected bool $isEnded     = false;

  function __construct(?Pipeline $parent = null)
  {
    Stat::$pipeline->add();
    if(!static::isSingleton())
      Loop_LeakTracker::register($this);
    $parent?->registerChild($this);
  }

  /** При уничтожении объекта автоматически уведомляем родителя для самоочистки */
  function __destruct()
  {
    if ($this->isValid() && !$this->isSingleton())
      $this->logicError('Pipeline was destroyed without finishing');
    $this->parent?->unregisterChild($this, 'destructor');
    Stat::$pipeline->remove();
  }
  
  function isValid() { return !$this->isCancelled && !$this->isEnded ; }
  
  //Chain of Responsibility / Monad
  /** Прием данных от родителя */
  final function process(Pipeline_Event $type, mixed $payload)
  {
  //Logger::log('process: ',$type, ': ', $payload);
    if (!$this->isValid()) $this->throwLogicError('You cannot call process() on a closed or completed pipeline. (' , static::class, ')');
      
    //Does not allow to start process due started another process
    $this->unlockPromise()?->await();
    $this->setProcessing(true);
    if ($type->isEnd())
    {
      $this->isEnded = true;
      $this->setProtected(false);
    }

    try
    {
      $this->onProcess($type, $payload);
    }
    catch (\Throwable $e)
    {
      $this->error('Uncathed exception')->exception($e);
      // Если в процессе обработки элемента произошла ошибка - перенаправляем её в поток ошибок
    //if (!$type->isError())
    //  $this->emitError($e);
    }
    finally
    {
      $this->setProcessing(false);
    }
  }
  
  protected abstract function onProcess(Pipeline_Event $type, mixed $payload): void;
  
  function canEmit   ():bool { return false; }
  function emit      ($data  ) { $this->emitEnd(); } // { $this->process(Pipeline_Event::DATA  ,$data  ); }
  function emitError ($error ) { $this->emitEnd(); } // { $this->process(Pipeline_Event::ERROR ,$error ); }
  function emitEnd   (       ) { $this->throwLogicError('Emit is not implemented'); } // { $this->process(Pipeline_Event::END   , null  ); }

  /** Защищает эндпоинты от ложного каскадного авто-удаления */
  protected function isEndpoint(): bool { return false; }
  protected static function isSingleton(): bool { return false; }

  // --- ФАБРИКА ОПЕРАТОРОВ (Fluent API) ---
  
  /**
   * Делает текущее звено защищенным от отмены.
   * Всё, что выше него, не прервется, пока данные не пройдут через этот шаг.
   */
  function uncancelable (                           ): self    { $this->setProtected(true); return $this; }
  function fork         (callable $setup            ): mixed   { return $setup($this)?? $this; }

  /** Создает конвейер из любого итерируемого источника (генератора) */
  static function from  (\Generator $generator      ): self    { return new Pipeline_Start_Generator ($generator             ); }
  /** Преобразует каждый элемент потока. */
  function map          (callable $callback         ): self    { return new Pipeline_Op_Map          ($this, $callback       ); }
  function filter       (callable $predicate        ): self    { return new Pipeline_Op_Filter       ($this, $predicate      ); }
  function slice        (int $skip, ?int $limit=null): self    { return new Pipeline_Op_Slice        ($this, $skip, $limit   ); }
  /** Группирует элементы по количеству или таймауту */
  function group        (int $count, float $timeout ): self    { return new Pipeline_Op_Group        ($this, $count, $timeout); }
  function unpack       (int $concurrency = 1       ): self    { return new Pipeline_Op_Unpack       ($this, $concurrency    ); }
  function concurrent   (int $max=1, int $depth=2   ): self    { return new Pipeline_Op_Concurrent   ($this, $max, $depth    ); }
  function catch        (callable $errorHandler     ): self    { return new Pipeline_Op_Catch        ($this, $errorHandler   ); }

// Capability
  function skip         (int $skip                  ): self    { return $this->slice($skip); }
  function limit        (int $limit                 ): self    { return $this->slice(0, $limit); }
  function take         (int $limit                 ): self    { return $this->limit($limit); } // C# (LINQ), RxJS.
  function flatMap      (callable $callback         ): self    { return $this->map($callback)->unpack(); }

  // --- ТЕРМИНАЛЬНЫЕ МЕТОДЫ ---
  
  function reduce(
    callable  $accumulator     ,
    mixed     $defaultValue    = null,
    ?bool     $hasDefaultValue = null
  )                                            : Promise { return new Pipeline_Final_Reduce  ($this, $accumulator, 
                                                                                   $defaultValue, $hasDefaultValue)->getPromise(); }
  function count   (int $startWith=0          ): Promise { return new Pipeline_Final_Count   ($this, $startWith   )->getPromise(); }
  function average (int $count=0, float $sum=0): Promise { return new Pipeline_Final_Average ($this, $count, $sum )->getPromise(); }
  function combine (callable $combine) { return $combine($this); }

  // --- СИНТАКСИЧЕСКИЙ САХАР НА БАЗЕ REDUCE ---
  
  /** Подсчет суммы элементов потока */
  function sum(mixed $def = 0    ): Promise { return $this->reduce(fn($acc, $val) =>     $acc+ $val  ,$def); }
  function max(mixed $def = null ): Promise { return $this->reduce(fn($acc, $val) => max($acc, $val) ,$def); }
  function min(mixed $def = null ): Promise { return $this->reduce(fn($acc, $val) => min($acc, $val) ,$def); }  
    
 /** Сбор всех элементов асинхронного потока в один обычный PHP-массив */
  function toList  (): Promise { return $this->reduce(function(&$acc, $val) { $acc[] = $val; return $acc; }, defaultValue: []); }
  function toArray (): Promise { return $this->toList(); }
  
  function first        (                           ): Promise { return new Pipeline_Final_First        ($this)->getPromise (   ); }
  function last         (                           ): Promise { return new Pipeline_Final_Last         ($this)->getPromise (   ); }
  /**
   * Терминальный метод. Запускает конвейер и возвращает Promise,
   * который выполнится по завершении и вернет финальное значение потока.
   * Принимает опциональный сигнал отмены.
   */
  function collect      (                           ): Promise { return new Pipeline_Final_Last         ($this)->getPromise (   ); }
  
  /**
   * Терминальный метод. Запускает весь конвейер и ждет окончания
   */
  function finish       (                           ): void    {        new Pipeline_Final_Finish       ($this)->wait       (   ); }
  function run          (                           ): void    { $this->collect(); }

//****************************************************************
// Locking functional

  protected ?Deferred $lockDeferred  =null  ;
  protected  bool     $isLocked      =false ; //Общая блокировка пайплайна
  protected  bool     $isProcessing  =false ; //Устанавливается, когда пайплайн уже запущен и находится в стадии выполнения.
  protected  bool     $isChildLocked =false ; //Применяется, если заблокирован пайплайн, запущенный внутри текущего (дочерний).
  protected  bool     $isOverloaded  =false ; //Используется, когда пайплайн не может принять новые задачи из-за высокой нагрузки.
  
  public function isLocked():bool { return $this->isLocked; }
  
  private function isActualLocked():bool { return $this->isProcessing || $this->isOverloaded || $this->isChildLocked; }
  
  private         function setProcessing (bool $lock) { $this->isProcessing  =$lock; $this->checkLocking(); }
  protected       function childLock     (bool $lock) { $this->isChildLocked =$lock; $this->checkLocking(); }
  protected final function setOverloaded (bool $lock) { $this->isOverloaded  =$lock; $this->checkLocking(); }
  
  private function checkLocking()
  {
    $isLocked = !$this->isLocked;
    if ($isLocked !== $this->isActualLocked()) return;
    $this->isLocked = $isLocked;
    
    if (!$isLocked)
      if ($deferred = $this->lockDeferred)
        if ($this->isValid())
          $deferred->resolve();
        else
          $deferred->reject('The pipeline was closed');
    
    $this->parent?->childLock($isLocked);
  }
  
  function unlockPromise():?Promise
  {
    if (!$this->isLocked()) return null;
    
    $deferred = $this->lockDeferred??= new Deferred();
    
    if (self::$debugLockPromise)
      $this->debugLockPromise($deferred->promise());
    
    return $deferred->promise();
  }

//****************************************************************
// Children/parent control

  protected ?Pipeline $parent = null;

  protected function setParent(Pipeline $parent):void { $this->parent=$parent; }
  
  protected abstract function registerChild(Pipeline $child): void;
  protected abstract function unregisterChild(Pipeline $child, $reason): void;

  /** Нахождение корня всей цепочки */
  protected function findRoot(): Pipeline_Start_Generator
  {
    $node = $this;
    while ($parent = $node->parent)
      $node = $parent;
    
    if (!$node instanceof Pipeline_Start_Generator) //TODO: Workaround
      $this->throwLogicError('The root element of a pipeline must be a descendant Pipeline_Start_Generator');
    
    return $node;
  }
  
  function getDepth()
  { // Находим глубину
    $res = 0;
    for ($item = $this->parent; $item; $item = $item->parent)
      $res++;
    return $res;
  }
  
//****************************************************************
// Aborting

  /** Метод инициации отмены снизу вверх */
  function abortPipeline($reason='abort'): void
  {
    if (!$this->isValid()) { $this->logicError('can\'t abort invald pipeline'); return; }
    
    $this->isCancelled = true;
    $this->onAbort();
    $this->setProtected(false);

    // Если есть родитель, уведомляем его, что мы отписались
    $this->parent?->unregisterChild($this, $reason);
  }
  
  /** Хук для кастомной очистки ресурсов (например, таймеров) */
  protected function onAbort(): void {}

  protected function onNoChild($reason)
  {
    // Если звено защищено, мы блокируем отмену вверх!
    if (!$this->isValid()  ) return;
    if ($this->isProtected ) return;
    if ($this->isEndpoint()) return;
    
    $this->abortPipeline($reason);
  }

//****************************************************************
// Protection

  protected bool $isProtected = false; // Защищено ли это звено от принудительной отмены (например, для записи критических логов/БД)
  
  protected static array $protected=[];
  
  function setProtected($v)
  {
    $this->isProtected = $v;
    $id = spl_object_id($this);
    if ($v)
      self::$protected[$id] = $this;
    else
      unset(self::$protected[$id]);
  }

//****************************************************************
// Debug function

  static bool $debugFullPath    = true  ;
  static bool $debugLockPromise = !false ;

  function error(...$args) { return Logger::error($this->debugName(), ': ', ...$args); }
  function debug(...$args) { return Logger::debug($this->debugName(), ': ', ...$args); }
  function logicError(...$args) { return $this->error(...$args)->backTrace(); }
  
  function throwLogicError(...$args):never
  {
    $this->logicError(...$args);
    $message = @join($args);
    throw new \LogicException($message);
  }

  function debugName()
  {
    if(!static::$debugFullPath)
      return 'Pipeline'.$this->GetDepth();
    $res=[];
    $this->buildDebugName($res);
    return join($res);
  }
  
  protected function buildDebugName(array &$res, $child=null)
  {
    $this->parent?->buildDebugName($res, $this);
    $res[] = array_last(explode('_', get_class($this))); //class
  }

  function debugLockPromise($promise)
  {
    $key='debugLock';
    if ($promise->hasHandler($key)) return;
    $promise->addHandler(fn($p)=>$this->debug('isLocked=false'), $key);
    
    Loop::delay(1, function() {
      if(!$this->isLocked()) return;
      $this->debug('isLocked=',$this->isProcessing? 'Proc':'', $this->isChildLocked? 'Child':'', $this->isOverloaded? 'Async':'');
      Loop::delay(1, Closure::getCurrent());
    });                         
    
    $this->debug('isLocked=true');
  }

//****************************************************************
}
