<?
abstract class Stream_Until_Base
{
  protected $reader;

//Search arguments
  public ?int  $chunkSize =null; 
  public ?int  $limit     =null;
  
//Result arguments
  public  bool $orEnd     =true;
  public ?bool $include   =null;
  
  function __construct($reader)
  {
    $this->reader = $reader;
  }

  function chunkSize (?int  $v =null) { $this->chunkSize =$v; return $this; }
  function limit     (?int  $v =null) { $this->limit     =$v; return $this; }
  function orEnd     ( bool $v =true) { $this->orEnd     =$v; return $this; }
  function include   (?bool $v =null) { $this->include   =$v; return $this; }
  
  abstract function onUntil($data, $offset): false|array;
  
  function onRead($reader)
  {
    $result = $reader->buffer_until($this->onUntil(...), $this->include); //TODO: $this->limit
    if ($result !== false)
      return $result;
    
    if (!$reader->isStreamEnd())
    { //TODO:          0?
      $reader->require(1, $this->chunkSize);
      return false;
    }
    
    if ($this->orEnd)
      return $reader->buffer_readAll();
    
    throw new Stream_Exception_End();
  }
  
  function read():mixed
  {
    $result = $this->onRead($this->reader);
    if ($result !== false)
      return $result;
    
    $promise = $this->reader->asyncTask($this->onRead(...));
    
    return $promise->await();
  }
}
