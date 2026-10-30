<?
abstract class Stream_IO_Base implements IStream_IO
{
  protected ?\WeakReference $streamRef=null;
  protected ?IStream $stream { get=>$this->streamRef?->get(); set(?IStream $v) { $this->streamRef=$v? \WeakReference::Create($v):null; } }
  
  static string $Stack_Class=Stream_Stack_Simple ::class;
//static string $Stack_Class=Stream_Stack_List   ::class;
//static string $Stack_Class=Stream_Stack_Tree   ::class;
  
  private IStream_Stack $stack { get=>$this->stack ??= self::createDefaulStack(); }
  
  static function createDefaulStack()
  {
    return new (self::$Stack_Class)();
  //return new Stream_Stack_List();
  //return new Stream_Stack_Tree();
  //return new Stream_Stack_Simple();
  }
  
  public function __construct(IStream $stream) { $this->stream = $stream; }
  
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
  
  
}
