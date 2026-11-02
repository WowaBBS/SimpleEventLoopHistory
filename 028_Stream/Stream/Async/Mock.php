<?
class Stream_Async_Mock extends Stream_Async_LoopBack
{
  public int $chunkSize = 1;
  
  function __construct(string $data = '')
  {
    parent::__construct($data);
    
    $this->getBuffer()->setCapacity(1);
  }

//****************************************************************
// Read

  function restRecvSize():int { return min(parent::restRecvSize(), $this->chunkSize); }
  
//****************************************************************
// Write

};
