<?
abstract class Stream_Until_Base
{
  public ?bool $include   =null;
  public ?int  $chunkSize =null; 
  public  bool $orEnd     =true;
  public ?int  $limit     =null;
  
  function __construct(
    ?bool $include   =null,
    ?int  $chunkSize =null, 
     bool $orEnd     =true,
    ?int  $limit     =null,
  )
  {
    $this->include   =$include   ;
    $this->chunkSize =$chunkSize ;
    $this->orEnd     =$orEnd     ;
    $this->limit     =$limit     ;
  }
  
  abstract function onUntil($data, $offset): false|array;
  
  function onRead($reader)
  {
    $result = $reader->buffer_until($this->onUntil(...), $this->include); //TODO: $this->limit
    if ($result !== false)
      return $result;
    
    if (!$reader->isStreamEnd())
    {  //TODO:         0?
      $reader->require(1, $this->chunkSize);
      return false;
    }
    
    if ($this->orEnd)
      return $reader->buffer_readAll();
    
    throw new Stream_Exception_End();
  }
}
