<?
abstract class Stream_IO_Base implements IStream_IO, IDebug_Leak_Object
{
  protected IStream_Async $stream;
  
  public function __construct(IStream_Async $stream) { $this->stream = $stream; Debug_Leak_Tracker::register($this); }
  
  function leakDispose()
  {
    $this->stream?->leakDispose();
    $this->stream = Stream_Async_Null::get();
    $this->stream_pause();
    
    new ReflectionProperty(self::class, 'stack')->getRawValue($this)?->leakDispose();
    
    $this->stack = Stream_Stack_Null::get();
  }
  
  static string $Stack_Class=Stream_Stack_Simple ::class;
//static string $Stack_Class=Stream_Stack_Queue  ::class;
//static string $Stack_Class=Stream_Stack_Tree   ::class;
  
  private IStream_Stack $stack { get=>$this->stack ??= self::createDefaulStack(); }
  
  static function createDefaulStack()
  {
    return new (self::$Stack_Class)();
  }
  
  abstract protected function stream_resume();
  abstract protected function stream_pause();
  
  protected function stack_add($readFunc, $deferred)
  {
    $had = $this->stack->has();
    $this->stack->add($readFunc, $deferred);
    if (!$had)
      $this->stream_resume();
  }
  
  protected function stack_call(...$args)
  {
    $result = $this->stack->call(...$args);
    if (!$this->stack->has())
      $this->stream_pause();
    return $result;
  }
//****************************************************************
// Task

  /**
   * Поставить задачу на чтение/запись в стек.
   * @param callable $readFunc Функция чтения: fn(Stream_Reader $r, string $buf): bool
   * @param callable|null $callback Колбэк завершения: fn(?\Throwable $error)
   */
  function asyncTask(callable $ioFunc):Promise
  {
    $deferred = new Deferred();
    $this->stack_add($ioFunc, $deferred);
    return $deferred->Promise();
  }
  
  /**
   * Поставить задачу на чтение/запись в стек.
   * @param callable $readFunc Функция чтения: fn(Stream_Reader $r, string $buf): bool
   * @param callable|null $callback Колбэк завершения: fn(?\Throwable $error)
   */
  function addTask(callable $ioFunc): void
  {
    $this->stack_add($ioFunc);
  }

  /** Выбросить ошибку формата или сетевого сбоя прямо во время чтения/записи */
  function raiseError(\Throwable $error): void
  {
    $current = array_pop($this->stack);
    if ($current && $current['callback'])
      ($current['callback'])($error);
    
    $this->execute(); // Продолжаем крутить стек для остальных задач, если они есть
  }

  protected function execute(): void
  {
    $this->stack_call($this);
  }
  
//****************************************************************
}
