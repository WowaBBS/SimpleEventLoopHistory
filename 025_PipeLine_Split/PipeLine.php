<?
//Fluent API
abstract class Pipeline
{
  protected ?Pipeline $parent = null;

  /** @var \WeakReference[] Храним слабые ссылки по spl_object_id на дочерние звенья */
  protected array $children = [];

  protected bool $isCancelled = false; // Был ли этот конкретный шаг отменен
  protected bool $isProtected = false; // Защищено ли это звено от принудительной отмены (например, для записи критических логов/БД)
  protected bool $isEnded     = false;

  function __construct(?Pipeline $parent = null)
  {
    $this->parent = $parent;
    $parent?->registerChild($this);
  }

  /** При уничтожении объекта автоматически уведомляем родителя для самоочистки */
  function __destruct()
  {
    $this->parent?->unregisterChild($this, 'destructor');
  }
  
  /** Безопасное добавление слабой ссылки на ребенка */
  protected function registerChild(Pipeline $child): void { $this->children[spl_object_id($child)] = \WeakReference::create($child); }

  /** Родитель сам управляет списком детей и решает, тушить ли себя */
  function unregisterChild(Pipeline $child, ...$reason): void
  {
    unset($this->children[spl_object_id($child)]);
    
    // КРИТИЧЕСКОЕ ПРАВИЛО: Родитель отменяется только если у него
    // НЕ ОСТАЛОСЬ других активных дочерних потребителей!
    if ($this->children    ) return;
    
    // Если звено защищено, мы блокируем отмену вверх!
    if ($this->isProtected ) return;
    if ($this->isEndpoint()) return;
    if ($this->isCancelled ) return;
    if ($this->isEnded     ) return;
    
    $this->abortPipeline(...$reason);
  }

  /** Нахождение корня всей цепочки */
  protected function findRoot(): Pipeline_Start_Generator
  {
    $node = $this;
    while ($parent = $node->parent)
      $node = $parent;
    
    if (!$node instanceof Pipeline_Start_Generator)
      throw new \LogicException("The root element of a pipeline must be a descendant Pipeline_Start_Generator");
    
    return $node;
  }

  /** Прием данных от родителя */
  final function process(mixed $item): void
  {
    if ($this->isCancelled || $this->isEnded)
      throw new \LogicException("You cannot call process() on a closed or completed pipeline. (" . static::class . ")");

    $this->onProcess($item);
  }

  /** Сигнал завершения от родителя */
  final function processEnd(): void
  {
    if ($this->isCancelled || $this->isEnded)
      throw new \LogicException("You cannot call processEnd() on a closed or completed pipeline. (" . static::class . ")");
    
    $this->onProcessEnd();
  }

  /** Перегружаемый метод обработки для дочерних классов */
  protected function onProcess(mixed $item): void { $this->emit($item); }

  /** Перегружаемый метод завершения для дочерних классов */
  protected function onProcessEnd(): void { $this->emitEnd(); } // По умолчанию просто пробрасываем сигнал конца дальше

  /** Раздача элемента всем детям */
  protected function emit(mixed $item): void
  {
    if ($this->isCancelled) return;
    if ($this->isEnded) { Logger::error($this->debugName(),'.emit has already finished')->BackTrace(); return; }
    
    foreach ($this->children as $child)
      $child->get()->process($item);
  }

  /** Сигнал завершения вниз */
  protected function emitEnd(): void
  {
    if ($this->isEnded) { Logger::error($this->debugName(),'.emitEnd has already finished')->BackTrace(); return; }
    
    $this->isEnded = true;
    
    foreach ($this->children as $child)
      $child->get()->processEnd();
  }

  /** Защищает эндпоинты от ложного каскадного авто-удаления */
  protected function isEndpoint(): bool { return false; }

  /** Метод инициации отмены снизу вверх */
  function abortPipeline(...$reason): void
  {
    if ($this->isCancelled || $this->isEnded)
    {
      Logger::error($this->debugName(), ' can\'t abort because of ',
        $this->isEnded     ?'Ended'  :(
        $this->isCancelled ?'Cancelled' :'Unknown')
      );
      return;
    }
    
    $this->isCancelled = true;
    $this->onAbort();

    // Если есть родитель, уведомляем его, что мы отписались
    $this->parent?->unregisterChild($this, ...$reason);
  }
  
  /** Хук для кастомной очистки ресурсов (например, таймеров) */
  protected function onAbort(): void {}

  // --- ФАБРИКА ОПЕРАТОРОВ (Fluent API) ---
  
  /**
   * Делает текущее звено защищенным от отмены.
   * Всё, что выше него, не прервется, пока данные не пройдут через этот шаг.
   */
  function uncancelable    (                           ): self    { $this->isProtected = true; return $this; }
  function fork            (callable $setup            ): mixed   { return $setup($this)?? $this; }

  /** Создает конвейер из любого итерируемого источника (генератора) */
  static function from     (\Generator $generator      ): self    { return new Pipeline_Start_Generator    ($generator             ); }
  /** Преобразует каждый элемент потока. */
  function map             (callable $callback         ): self    { return new Pipeline_Op_Map             ($this, $callback       ); }
  /** Группирует элементы по количеству или таймауту */
  function group           (int $count, float $timeout ): self    { return new Pipeline_Op_Group           ($this, $count, $timeout); }
  function sum             (callable $selector         ): self    { return new Pipeline_Op_Sum             ($this, $selector       ); }
  function unpack          (                           ): self    { return new Pipeline_Op_Unpack          ($this                  ); }
  function resolvePromises (                           ): self    { return new Pipeline_Op_ResolvePromises ($this                  ); }

  // --- ТЕРМИНАЛЬНЫЕ МЕТОДЫ ---
  
  /**
   * Терминальный метод. Запускает конвейер и возвращает Promise,
   * который выполнится по завершении и вернет финальное значение потока.
   * Принимает опциональный сигнал отмены.
   */
  function collect         (                           ): Promise { return new Pipeline_Final_Collect      ($this)->getPromise (   ); }
  
  /**
   * Терминальный метод. Запускает весь конвейер и ждет окончания
   */
  function finish          (                           ): void    {        new Pipeline_Final_Finish       ($this)->wait       (   ); }
  function run             (                           ): void    { $this->collect(); }

//****************************************************************
// Debug function

  function getDepth()
  {
    // Находим глубину
    $res = 0;
    for ($item = $this->parent; $item; $item = $item->parent)
      $res++;
    return $res;
  }
  
  function debugName()
  {
    return 'Pipeline'.$this->GetDepth();
    $res=[];
    $this->_debugName($res);
    return join($res);
  }
  
  function _debugName(array &$res, $child=null)
  {
    $this->parent?->_debugName($res, $this);
    $res[]=Get_Class($this);
    if(!$child) return;
    $children=$this->children;
    $id=spl_object_id($child);
    if(!isset($children[$id])) { $res[]='?.'; return; }
    if(count($children)===1) { $res[]='.'; return; }
    $i=0;
    foreach($children as $item_id=>$item)
      if($item_id===$id)
        break;
      else
        $i++;
    array_push($res, '[', $i, '].');
  }
//****************************************************************
}
