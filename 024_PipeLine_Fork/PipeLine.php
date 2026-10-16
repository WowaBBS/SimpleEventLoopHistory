<?
class Pipeline
{
  private ?\Generator $rootGenerator = null;
  private ?Pipeline $parent = null;

  /** @var \WeakReference[] Список слабых ссылок на дочерние звенья */
  private array $children = [];

  /** @var \Closure|null Функция-трансформатор текущего звена */
  private ?\Closure $transform = null;
  private ?\Closure $onEnd     = null;

  private bool $isCancelled = false; // Был ли этот конкретный шаг отменен
  private bool $isFinished  = false;
  private bool $isProtected = false; // Защищено ли это звено от принудительной отмены (например, для записи критических логов/БД)

  // Ссылка на финальный Deferred (нужен только для терминального звена)
  private ?Deferred $deferred = null;
  private mixed $finalValue = null;

  function __construct(?\Generator $generator = null, ?Pipeline $parent = null, ?callable $transform = null, ?callable $onEnd = null)
  {
    $this->rootGenerator = $generator;
    $this->parent        = $parent;
    $this->transform     = $transform ? \Closure::fromCallable($transform ) : null;
    $this->onEnd         = $onEnd     ? \Closure::fromCallable($onEnd     ) : null;

    $parent?->addChild($this);
  }
  
  function __destruct()
  {
    $this->parent?->abortedPipeline($this, 'destructor');
  }
  
  /**
   * Создает конвейер из любого итерируемого источника (генератора)
   */
  static function from(\Generator $generator): self { return new self($generator); }

  /**
   * Делает текущее звено защищенным от отмены.
   * Всё, что выше него, не прервется, пока данные не пройдут через этот шаг.
   */
  function uncancelable(): self
  {
    $this->isProtected = true;
    return $this;
  }

  /**
   * Безопасное добавление слабой ссылки на ребенка
   */
  private function addChild(Pipeline $child): void
  {
    $this->children[Spl_Object_Id($child)] = \WeakReference::create($child);
  }

  /**
   * Главный метод обработки элемента, вызываемый родителем
   */
  function process(mixed $item): void
  {
    if ($this->isCancelled) { Logger::error('Pipeline', $this->getDepth(),'.process has already canceled')->BackTrace(); return; }
    if ($this->isFinished) { Logger::error('Pipeline', $this->getDepth(),'.process has already finished')->BackTrace(); return; }

    // Если у этого звена есть трансформация (map, group, sum) - выполняем её
    if ($this->transform)
      ($this->transform)($this, $item);
  }

  /**
   * Сигнал об окончания данных, идущий сверху вниз
   */
  function processEnd(): void
  {
    if ($this->isCancelled) { Logger::error('Pipeline', $this->getDepth(),'.processEnd has already canceled')->BackTrace(); return; }
    if ($this->isFinished) { Logger::error('Pipeline', $this->getDepth(),'.processEnd has already finished')->BackTrace(); return; }
    $this->isFinished=True;
    
    if($onEnd=$this->onEnd)
      $onEnd($this);

    if ($this->deferred)
    {
      $this->deferred->resolve($this->finalValue);
      return;
    }

    foreach ($this->children as $child)
      $child->get()->processEnd();
  }

  /**
   * Раздача элемента всем живым детям
   */
  protected function emit(mixed $item): void
  {
    if ($this->isCancelled) return;
    
    // Если это конечное (терминальное) звено, просто аккумулируем результат
    if ($this->deferred)
      $this->finalValue = $item;

    foreach ($this->children as $key => $child)
      $child->get()->process($item);
  }

  /**
   * Метод инициации отмены снизу вверх
   */
  function abortPipeline(...$reason): void
  {
    if ($this->isCancelled || $this->isFinished)
    {
      Logger::error('Pipeline', $this->getDepth(), ' can\'t abort because of ',
        $this->isFinished  ?'Finished'  :(
        $this->isCancelled ?'Cancelled' :'Unknown')
      );
      return;
    }
    
    $this->isCancelled = true;

    // Если есть родитель, уведомляем его, что мы отписались
    $this->parent?->abortedPipeline($this, ...$reason);
  }
  
  function abortedPipeline($child, ...$reason)
  {
    UnSet($this->children[Spl_Object_Id($child)]);
    
    // КРИТИЧЕСКОЕ ПРАВИЛО: Родитель отменяется только если у него
    // НЕ ОСТАЛОСЬ других активных дочерних потребителей!
    if($this->children) return;
    // Если звено защищено, мы блокируем отмену вверх!
    if($this->isProtected) return;
    if($this->deferred) return;
    if($this->isCancelled || $this->isFinished) return;
    
    $this->abortPipeline(...$reason);
  }

  // --- ОПЕРАТОРЫ КОНВЕЙЕРА ---

  /**
   * Преобразует каждый элемент потока.
   * Если колбэк возвращает Promise, дожидается его выполнения.
   */
  function map(callable $callback): self
  {
    return new self(null, $this, function($pipeline, $item) use ($callback) {
      $result = $callback($item);

      // Если внутри колбэка вернули массив с промисами, резолвим их
      if (is_array($result))
        $result = $this->resolveArrayWithPromises($result);
      if ($result instanceof Promise)
        $result = $result->await();

      $pipeline->emit($result);
    });
  }
  
  function expand(): self
  {
    return new self(null, $this, function($pipeline, $item) {
      foreach ($item as $subItem)
        $pipeline->emit($subItem);
    });
  }

  /**
   * Группирует элементы по количеству или таймауту
   */
  function group(int $count, float $timeout): self
  {
    $chunk = [];
    $lastFlush = microtime(true);

    $child = new self(null, $this,
      function($pipeline, $item) use (&$chunk, $count, &$lastFlush) 
      {
        $chunk[] = $item;
        if (count($chunk) >= $count) 
        {
          $pipeline->emit($chunk);
          $chunk = [];
          $lastFlush = microtime(true);
        }
      },
      function($pipeline) use (&$chunk, &$lastFlush) 
      { //onEnd
        if(!$chunk) return;
        $pipeline->emit($chunk);
        $chunk = [];
        $lastFlush = microtime(true);
      }
    );

    // Таймер для контроля таймаута группы
    Loop::delay($timeout, function() use (&$chunk, $timeout, &$lastFlush, $child) {
      $checkTimeout = function() use (&$chunk, $timeout, &$lastFlush, &$checkTimeout, $child) {
        if ($child->isCancelled) return;
        if ($child->isFinished) return;

        if (!empty($chunk) && (microtime(true) - $lastFlush) >= $timeout)
        {
          $child->emit($chunk);
          $chunk = [];
          $lastFlush = microtime(true);
        }
        Loop::delay($timeout, $checkTimeout);
      };
      $checkTimeout();
    });

    return $child;
  }

  function sum(callable $selector): self
  {
    $total = 0;
    return new self(null, $this, 
      function($pipeline, $item) use (&$total, $selector)
      {
        $total += $selector($item);
      },
      function($pipeline) use (&$total, $selector) 
      { //onEnd
        $pipeline->emit($total);
      }
    );
  }

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
  function collect(): Promise
  {
  //$this->uncancelable();
    $this->deferred = new Deferred();
    $promise = $this->deferred->promise();

    $promise->setOnCancel($this->abortPipeline(...));

    $root=$this->getRoot();
    
    // Запускаем итерацию в корне
    $root->startRootIteration();

    return $promise;
  }

  function getDepth()
  {
    // Находим глубину
    $res = 0;
    for ($item = $this->parent; $item; $item = $item->parent)
      $res++;
    return $res;
  }
  
  private function getRoot()
  {
    // Находим корень дерева
    $root = $this;
    while ($parent=$root->parent)
      $root = $parent;
    return $root;
  }
  
  Var $isRuning=false;

  private function rootIteration(): void
  {
    if($this->isRuning) return;
    $this->isRuning=true;
    try
    {
      while ($this->rootGenerator->valid())
      {
        // Проверяем, есть ли вообще смысл крутить цикл
        if ($this->isCancelled || empty($this->children))
          return;

        $item = $this->rootGenerator->current();
        $this->rootGenerator->next();

        // Раздаем элемент по дереву слабых ссылок
        $this->emit($item);

        sleepAsync(0)->await();
      }

      $this->processEnd();
    //$this->emitEnd();
    } 
    catch (\Throwable $e)
    {
      Logger::exception($e, 'Pipeline.rootIteration');
      $this->abortPipeline();
    }
  }

  private function startRootIteration(): void
  {
    if ($this->rootGenerator === null) return;

    Loop::async($this->rootIteration(...));
  }

  /**
   * Терминальный метод для простого запуска.
   * Запускает конвейер <вхолостую> ради выполнения побочных эффектов.
   * Ничего не возвращает (void).
   */

  function run(): void { $this->collect(); }

  /**
   * Вспомогательный метод для глубокого разрешения промисов в массиве
   */
  private function resolveArrayWithPromises(array $array): array 
  {
    $promises =[];
    foreach ($array as $key => $value)
      if ($value instanceof Promise)
      {
        // Если весь пайплайн отменяется, гасим внутренние промисы
        $promises[$key] = $value;
        $this->signalInternalPromiseCancellation($value);
      }

    if (!empty($promises))
    {
      $settled = Promise::allSettled($promises)->await();
      foreach ($settled as $key => $res)
      {
        // Заменяем промис на его реальный результат (или объект ответа)
        $array[$key] = $res['status'] === 'fulfilled' ? $res['value'] : $res['reason'];
      }
    }
    return $array;
  }

  private function signalInternalPromiseCancellation(Promise $promise): void
  {
    // Кастомный хелпер: если пайплайн отменяется, мы пинаем промис
    Loop::async(function() use ($promise) {
      while (true)
      {
        if ($this->isCancelled)
        {
          $promise->abort("Пайплайн отменен.");
          break;
        }
        if ($promise->await() !== null) // Если промис выполнился сам, выходим
          break;
        sleepAsync(0.01)->await();
      }
    });
  }
}