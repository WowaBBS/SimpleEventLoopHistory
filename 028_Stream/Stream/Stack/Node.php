<?
class Stream_Stack_Node
{
  var ?self     $parent   ;
  var           $handler  ;
  var ?Deferred $deferred ;
  var ?self     $next     ;
  var ?self     $first    ;
  var ?self     $last     ;
  
  function __construct(callable $handler, ?Deferred $deferred=null)
  {
    $this->handler  =$handler  ;
    $this->deferred =$deferred ;
  }
  
  function add(self $v)
  {
    if ($v->parent) throw new \LogicException('Handler has already handling');
    $v->parent = $this;
    if ($last = $this->last)
      $last->next=$v;
    else
      $this->first=$v;
    $this->last = $v;
  }
  
  function pop(): self
  {
    $res = $last->first;
    if (!$res)
      return null;
    $next = $res->next;
    $res->first = $next;
    if ($next)
      $last->last = null;
    $res->next = null;
    return $res;
  }
  
  function invoke(...$args)
  {
    return ($this->handler)(...$args);
  }
  
  function addTo(self &$current)
  {
    $current ??= new Stream_Handler(fn()=>true);
    $current->add($this);
    return $this;
  }
}
