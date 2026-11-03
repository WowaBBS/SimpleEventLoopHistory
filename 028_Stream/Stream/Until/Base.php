<?
abstract class Stream_Until_Base
{
  protected $reader;

//Search arguments
  public ?int  $chunkSize =null; 
  public ?int  $limit     =null;
  
//Result arguments
  public  bool $strict    =false;
  public ?bool $include   =null;
  
  function __construct($reader)
  {
    $this->reader = $reader;
  }

  function chunkSize (?int  $v =null) { $this->chunkSize =$v; return $this; }
  function limit     (?int  $v =null) { $this->limit     =$v; return $this; }
  function strict    ( bool $v =true) { $this->strict    =$v; return $this; }
  function include   (?bool $v =null) { $this->include   =$v; return $this; }
  
  abstract function onUntil($data, $offset): ?array;
  
  function onRead($reader)
  {
    $result = $reader->buffer_until($this->onUntil(...), $this->include); //TODO: $this->limit
    if ($result !== null)
      return $result;
    
    if (!$reader->isRecvEnd())
    { //TODO:          0?
      $reader->require(1, $this->chunkSize);
      return null;
    }
    
    if (!$this->strict)
      return $reader->buffer_readAll();
    
    throw new Stream_Exception_End();
  }
  
  function read():mixed
  {
    $result = $this->onRead($this->reader);
    if (Stream_Stack_Base::isFinished($result))
      return $result;
    
    $promise = $this->reader->asyncTask($this->onRead(...));
    
    return $promise->await();
  }
}
