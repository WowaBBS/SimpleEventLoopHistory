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
    $this->emitEnd();
  }

  /** Перегружаемый метод обработки для дочерних классов */
  protected function onProcess(mixed $item): void { $this->emit($item); }

  /** Перегружаемый метод завершения для дочерних классов */
  protected function onProcessEnd(): void { }

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
  static function from     (\Generator $generator      ): self    { return new Pipeline_Start_Generator              ($generator             ); }
  /** Преобразует каждый элемент потока. */
  function map             (callable $callback         ): self    { return new Pipeline_Intermediate_Map             ($this, $callback       ); }
  /** Группирует элементы по количеству или таймауту */
  function group           (int $count, float $timeout ): self    { return new Pipeline_Intermediate_Group           ($this, $count, $timeout); }
  function sum             (callable $selector         ): self    { return new Pipeline_Intermediate_Sum             ($this, $selector       ); }
  function unpack          (                           ): self    { return new Pipeline_Intermediate_Unpack          ($this                  ); }
  function resolvePromises (                           ): self    { return new Pipeline_Intermediate_ResolvePromises ($this                  ); }

  // --- ТЕРМИНАЛЬНЫЕ МЕТОДЫ ---
  
  /**
   * Терминальный метод. Запускает конвейер и возвращает Promise,
   * который выполнится по завершении и вернет финальное значение потока.
   * Принимает опциональный сигнал отмены.
   */
  function collect         (                           ): Promise { return new Pipeline_Final_Collect                ($this)->getPromise(    ); }
  
  /**
   * Терминальный метод. Запускает весь конвейер и ждет окончания
   */
  function finish          (                           ): void    { $this->collect()->await(); }
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

//****************************************************************
// Стартовые операторы (Pipeline_Start_*)

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

    // Вместо анонимной функции запускаем именованный метод класса для красивого стек-трейса
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
  
  /** Вынесено в метод для читаемости стек-трейса */
  private function rootGeneratorItemFetch(): mixed
  {
    $item = $this->generator->current();
    $this->generator->next();
    return $item;
  }
}

//****************************************************************
// Промежуточные операторы (Pipeline_Intermediate_*)

/** Оператор трансформации MAP */
class Pipeline_Intermediate_Map extends Pipeline
{
  /** @var callable */
  private $callback;

  function __construct(Pipeline $parent, callable $callback)
  {
    parent::__construct($parent);
    $this->callback = $callback;
  }

  protected function onProcess(mixed $item): void
  {
    $result = ($this->callback)($item);
    if ($result instanceof Promise)
      $result = $result->await();
    
    $this->emit($result);
  }
}

/** Оператор одноуровневой развёртки UNPACK */
class Pipeline_Intermediate_Unpack extends Pipeline
{
  protected function onProcess(mixed $item): void
  {
    if (!is_iterable($item))
      throw new \InvalidArgumentException("The unpack operator expects iterable data (array|\Traversable)");
    
    foreach ($item as $subItem)
      $this->emit($subItem);
  }
}

/** Оператор параллельного разрешения промисов */
class Pipeline_Intermediate_ResolvePromises extends Pipeline
{
  protected function onProcess(mixed $item): void
  {
    if (is_array($item))
    {
      $promises = [];
      foreach ($item as $key => $value)
        if ($value instanceof Promise)
          $promises[$key] = $value;
      
      if (!empty($promises))
      {
        $settled = Promise::allSettled($promises)->await();
        foreach ($settled as $key => $res)
          $item[$key] = $res['status'] === 'fulfilled' ? $res['value'] : $res['reason'];
      }
      
      $this->emit($item);
    }
    else
    {
      if ($item instanceof Promise)
        $item = $item->await();
      
      $this->emit($item);
    }
  }
}

/** Оператор суммирования SUM */
class Pipeline_Intermediate_Sum extends Pipeline
{
  /** @var callable */
  private $selector;
  private float $total = 0;

  function __construct(Pipeline $parent, callable $selector)
  {
    parent::__construct($parent);
    $this->selector = $selector;
  }

  protected function onProcess(mixed $item): void
  {
    $this->total += ($this->selector)($item);
  }

  protected function onProcessEnd(): void
  {
    // Выдаёт финальную сумму в поток только при завершении
    $this->emit($this->total);
  }
}

/** Оператор группировки по времени и количеству GROUP */
class Pipeline_Intermediate_Group extends Pipeline
{
  private  int    $count     ;
  private  float  $timeout   ;
  private  array  $chunk     = [];
  private  float  $lastFlush ;
  private ?string $timerId   = null;

  function __construct(Pipeline $parent, int $count, float $timeout)
  {
    parent::__construct($parent);
    $this->count     = $count;
    $this->timeout   = $timeout;
    $this->lastFlush = microtime(true);
    $this->startTimer();
  }

  protected function onProcess(mixed $item): void
  {
    $this->chunk[] = $item;
    if (count($this->chunk) >= $this->count)
      $this->flush();
  }

  protected function onProcessEnd(): void
  {
    $this->stopTimer(); // Стопаем таймер, чтобы не тикал вхолостую
    if (!empty($this->chunk))
      $this->flush(); // Сбрасываем остатки
  }

  private function flush(): void
  {
    if (empty($this->chunk)) return;
    $this->emit($this->chunk);
    $this->chunk = [];
    $this->lastFlush = microtime(true);
  }

  private function startTimer(): void
  {
    // Так как таймер работает в основном потоке Loop,
    // мы запускаем проверку таймаута через Loop::async, чтобы внутри можно было авейтить!
    $this->timerId = uniqid('timer_', true);

    Loop::async($this->timerLoop(...));
  }
  
  private function timerLoop()
  {
    while (!$this->isCancelled && $this->timerId !== null)
    {
      sleepAsync($this->timeout)->await(); // Здесь await абсолютно легален!

      if (!empty($this->chunk) && (microtime(true) - $this->lastFlush) >= $this->timeout)
        $this->flush();
    }
  }

  private function stopTimer(): void { $this->timerId = null; }
  protected function onAbort(): void { $this->stopTimer(); }
}

//****************************************************************
// Финальные операторы (Pipeline_Final_*)

/** Конечная точка COLLECT (Возвращает Promise) */
class Pipeline_Final_Collect extends Pipeline
{
  private Deferred $deferred; // Ссылка на финальный Deferred
  private mixed $result = null;

  function __construct(Pipeline $parent)
  {
    parent::__construct($parent);
    $this->deferred = new Deferred();
    $this->deferred->promise()->setOnCancel($this->abortPipeline(...));

    // Запускаем итерацию в корне
    $this->findRoot()->startIteration();
  }

  // Защищает эндпоинт от ложного авто-удаления
  protected function isEndpoint(): bool { return true; }
  
  function getPromise(): Promise { return $this->deferred->promise(); }

  // Получает данные ИЗ ЕМИТА предыдущего пайплайна, сохраняя стейт
  protected function onProcess(mixed $item): void
  {
    $this->result = $item;
  }

  // Завершается строго по событию конца потока
  protected function onProcessEnd(): void
  {
    $this->deferred->resolve($this->result);
  }
}