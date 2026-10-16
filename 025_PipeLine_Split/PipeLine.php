<?
abstract class Pipeline
{
  protected ?Pipeline $parent = null;

  /** @var \WeakReference[] Список слабых ссылок на дочерние звенья */
  protected array $children = [];

  protected bool $isCancelled = false; // Был ли этот конкретный шаг отменен
  protected bool $isProtected = false; // Защищено ли это звено от принудительной отмены (например, для записи критических логов/БД)
  protected bool $isFinished  = false;

  function __construct(?Pipeline $parent = null)
  {
    $this->parent = $parent;
    $parent?->addChild($this);
  }

  function __destruct()
  {
    if ($parent=$this->parent)
      $parent->abortedPipeline($this, 'destructor');
  }
  
  /**
   * Делает текущее звено защищенным от отмены.
   * Всё, что выше него, не прервется, пока данные не пройдут через этот шаг.
   */
  function uncancelable(): self { $this->isProtected = true; return $this; }

  /**
   * Безопасное добавление слабой ссылки на ребенка
   */
  private function addChild(Pipeline $child): void
  {
    $this->children[spl_object_id($child)] = \WeakReference::create($child);
  }

  /** Нахождение корня всей цепочки */
  protected function findRoot(): PipelineStart
  {
    $node = $this;
    while ($parent=$node->parent)
      $node = $parent;
    
    if (!$node instanceof PipelineStart)
      throw new \LogicException("Корневой элемент пайплайна должен быть наследником PipelineStart");
    
    return $node;
  }

  function getDepth()
  {
    // Находим глубину
    $res = 0;
    for ($item = $this->parent; $item; $item = $item->parent)
      $res++;
    return $res;
  }
  
  /** Главный метод обработки элемента, вызываемый родителем */
  abstract function process(mixed $item): void;

  /** Сигнал об окончания данных, идущий сверху вниз */
  abstract function processEnd(): void;

  /** Раздача элемента всем живым детям */
  protected function emit(mixed $item): void
  {
    if ($this->isCancelled) return;
    if ($this->isFinished) { Logger::error($this->debugName(),'.emitEnd has already finished')->BackTrace(); return; }

    foreach ($this->children as $key => $child)
      $child->get()->process($item);
  }

  /** Сигнал завершения вниз */
  protected function emitEnd(): void
  {
    if ($this->isFinished) { Logger::error($this->debugName(),'.emitEnd has already finished')->BackTrace(); return; }
    $this->isFinished=true;
    foreach ($this->children as $childRef)
    {
      $child = $childRef->get();
      if ($child)
        $child->processEnd();
    }
  }

  /** Защищает эндпоинты от ложного каскадного авто-удаления */
  protected function isEndpoint(): bool { return false; }

  /** Метод инициации отмены снизу вверх */
  function abortPipeline(...$reason): void
  {
    if ($this->isCancelled || $this->isFinished)
    {
      Logger::error($this->debugName(), ' can\'t abort because of ',
        $this->isFinished  ?'Finished'  :(
        $this->isCancelled ?'Cancelled' :'Unknown')
      );
      return;
    }
    
    $this->isCancelled = true;

    $this->onAbort();

    // Если есть родитель, уведомляем его, что мы отписались
    $this->parent?->abortedPipeline($this, ...$reason);
  }
  
  function abortedPipeline($child, ...$reason)
  {
    unset($this->children[spl_object_id($child)]);
    
    // КРИТИЧЕСКОЕ ПРАВИЛО: Родитель отменяется только если у него
    // НЕ ОСТАЛОСЬ других активных дочерних потребителей!
    if($this->children) return;
    // Если звено защищено, мы блокируем отмену вверх!
    if($this->isProtected) return;
    if($this->isEndpoint()) return;
    if($this->isCancelled || $this->isFinished) return;
    
    $this->abortPipeline(...$reason);
  }

  /** Хук для кастомной очистки ресурсов (например, таймеров) */
  protected function onAbort(): void {}

  /**
   * Создает конвейер из любого итерируемого источника (генератора)
   */
  static function from(\Generator $generator): PipelineStart { return new PipelineStart($generator); }

  // --- ТЕРМИНАЛЬНЫЕ МЕТОДЫ ---

  /**
   * Терминальный метод. Запускает весь конвейер и ждет окончания
   */
  function finish(): void
  {
    // Просто прокручиваем генератор до конца, активируя всю цепочку
    $this->collect()->await();
  }

  /**
   * Терминальный метод. Запускает конвейер и возвращает Promise,
   * который выполнится по завершении и вернет финальное значение потока.
   * Принимает опциональный сигнал отмены.
   */
  function collect(): Promise { $endpoint = new CollectFinal($this); return $endpoint->getPromise(); }

  // --- ФАБРИКА ОПЕРАТОРОВ (Fluent API) ---

  /** Преобразует каждый элемент потока. */
  function map(callable $callback): MapIntermediate { return new MapIntermediate($this, $callback); }
  /** Группирует элементы по количеству или таймауту */
  function group(int $count, float $timeout): GroupIntermediate { return new GroupIntermediate($this, $count, $timeout); }
  function sum(callable $selector): SumIntermediate { return new SumIntermediate($this, $selector); }
  function flatMap(): FlatMapIntermediate { return new FlatMapIntermediate($this); }
  function resolvePromises(): ResolvePromisesIntermediate { return new ResolvePromisesIntermediate($this); }
  function fork(callable $setup): self { $setup($this); return $this; }
  
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
}

class PipelineStart extends Pipeline
{
  private \Generator $generator;
  private bool $isStarted = false;

  function __construct(\Generator $generator)
  {
    parent::__construct(null);
    $this->generator = $generator;
  }

  function process(mixed $item): void {}
  function processEnd(): void {}

  function startIteration(): void
  {
    if ($this->isStarted) return;
    $this->isStarted = true;

    Loop::async(function() {
      try
      {
        while ($this->generator->valid())
        {
          if ($this->isCancelled || empty($this->children))
            return;

          $item = $this->rootGeneratorItemFetch();
          $this->emit($item);

          sleepAsync(0)->await();
        }
        $this->emitEnd();
      }
      catch (\Throwable $e)
      {
        $this->abortPipeline();
      }
    });
  }

  /** Вынесено в метод для читаемости стек-трейса */
  private function rootGeneratorItemFetch(): mixed
  {
    $item = $this->generator->current();
    $this->generator->next();
    return $item;
  }
}

/** Оператор трансформации MAP */
class MapIntermediate extends Pipeline
{
  /** @var callable */
  private $callback;

  function __construct(Pipeline $parent, callable $callback)
  {
    parent::__construct($parent);
    $this->callback = $callback;
  }

  function process(mixed $item): void 
  {
    $result = ($this->callback)($item);
    if ($result instanceof Promise)
      $result = $result->await();
    
    $this->emit($result);
  }

  function processEnd(): void { $this->emitEnd(); }
}

/** Оператор развёртки массивов FLATMAP */
class FlatMapIntermediate extends Pipeline
{
  function process(mixed $item): void
  {
    if (is_iterable($item))
    {
      foreach ($item as $subItem)
        $this->emit($subItem);
    }
    else
      $this->emit($item);
  }

  function processEnd(): void { $this->emitEnd(); }
}

/** Оператор параллельного разрешения промисов */
class ResolvePromisesIntermediate extends Pipeline
{
  function process(mixed $item): void
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

  function processEnd(): void { $this->emitEnd(); }
}

/** Оператор суммирования SUM */
class SumIntermediate extends Pipeline
{
  /** @var callable */
  private $selector;
  private float $total = 0;

  function __construct(Pipeline $parent, callable $selector)
  {
    parent::__construct($parent);
    $this->selector = $selector;
  }

  function process(mixed $item): void
  {
    // Больше не обрабатывает массивы скрыто, работает строго по контракту
    $this->total += ($this->selector)($item);
  }

  function processEnd(): void
  {
    // Выдаёт финальную сумму в поток только при завершении
    $this->emit($item ?? $this->total);
    $this->emitEnd();
  }
}

/** Оператор группировки по батчам GROUP */
class GroupIntermediate extends Pipeline
{
  private int $count;
  private float $timeout;
  private array $chunk = [];
  private float $lastFlush;
  private ?string $timerId = null;

  function __construct(Pipeline $parent, int $count, float $timeout)
  {
    parent::__construct($parent);
    $this->count = $count;
    $this->timeout = $timeout;
    $this->lastFlush = microtime(true);
    $this->startTimer();
  }

  function process(mixed $item): void
  {
    $this->chunk[] = $item;
    if (count($this->chunk) >= $this->count)
      $this->flush();
  }

  function processEnd(): void
  {
    $this->stopTimer(); // Стопаем таймер, чтобы не тикал вхолостую
    if (!empty($this->chunk))
      $this->flush(); // Сбрасываем остатки
    
    $this->emitEnd();
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

    Loop::async(function() {
      while (!$this->isCancelled && $this->timerId !== null)
      {
        sleepAsync($this->timeout)->await(); // Здесь await абсолютно легален!

        if (!empty($this->chunk) && (microtime(true) - $this->lastFlush) >= $this->timeout)
          $this->flush();
      }
    });
  }

  private function stopTimer(): void { $this->timerId = null; }
  protected function onAbort(): void { $this->stopTimer(); }
}

class CollectFinal extends Pipeline
{
  private Deferred $deferred; // Ссылка на финальный Deferred
  private mixed $result = null;

  function __construct(Pipeline $parent)
  {
    parent::__construct($parent);
    $this->deferred = new Deferred();
    $this->deferred->promise()->setOnCancel(fn() => $this->abortPipeline());

    // Запускаем итерацию в корне
    $this->findRoot()->startIteration();
  }

  // Защищает эндпоинт от ложного авто-удаления
  protected function isEndpoint(): bool { return true; }

  function getPromise(): Promise { return $this->deferred->promise(); }

  // Получает данные ИЗ ЕМИТА предыдущего пайплайна, сохраняя стейт
  function process(mixed $item): void { $this->result = $item; }

  // Завершается строго по событию конца потока
  function processEnd(): void { $this->deferred->resolve($this->result); }
}