<?
class Promise_Comb_Base
{
  function __construct(array $list, int $depth=1)
  {
    $this->deferred = new Deferred();
    $this->list     = $list  ;
    $this->depth    = $depth ;
  }
  
//****************************************************************
// Data

  protected array $list  = [] ;
  private   int   $depth = 1  ;

//****************************************************************
// Deferred

  private Deferred $deferred       ;
  
  function promise() { return $this->deferred->promise(); }
  protected function resolve($value) { $this->deferred->resolve($value); }
  protected function reject($reason) { $this->deferred->reject($reason); }

//****************************************************************
// Remaning

  protected int $remaining = 1  ;
  
  protected function add() { $this->remaining++; }
  protected function finish()
  {
    if (--$this->remaining) return;
    $this->onFinish();
  }
  
  protected function onFinish() { throw \LogicException('Undefined onFinish()'); }

//****************************************************************
// undefined

  static function undefined() { static $result=new class() {}; return $result; }

  function removeUndefined()
  {
    $list = &$this->list;
    $undefined = self::undefined();
    foreach ($list as $key => &$item)
      if ($item === $undefined)
        unset($list[$key]);
  }
  
//****************************************************************
};