<?
class Stream_Stack_Queue extends Stream_Stack_Base
{
  /** @var array Стек активных задач чтения (LIFO) */
  private array $stack   = [];
  private int   $current = 0;

  function has(): bool { return (bool)$this->stack; }
  function isEmpty(): bool { return count($this->stack) == 0; }
  
  function add(callable $handler, $deferred = null):void
  {
    if (!$this->stack && $this->current !== 0)
    {
      Logger::error('LogicError: handler add')->backtrace();
      $this->current = 0;
    }
    if ($deferred)
      array_splice($this->stack, $this->current, 0, [$deferred, $handler]);
  }

  function getHandler():callable
  {
    $idx = array_key_last($this->stack);
    $this->current = $idx;
    $handler = $this->stack[$idx];
    return $handler;
  }
  
  function call(...$args)
  {
    $idx = array_key_last($this->stack);
    $this->current = $idx;
    $handler = $this->stack[$idx];
    $result = $handler(...$args);
    if (self::isFinished($result))
    {
      $hasChild = count($this->stack) > $idx+1;
    //$this->stack[$idx] = 
    }

  }
}
