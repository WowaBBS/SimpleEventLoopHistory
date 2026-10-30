<?
class Stream_Mock extends Stream_Base
{
  private string $data      ;
  public  int $chunkSize = 1;
  
  function __construct(string $data='') { $this->data=$data; }
  
//****************************************************************
// Read

  private int    $readPos   =0;
  
  function recv(int $size): string
  {
    if ($size < $this->chunkSize)
      $size = $this->chunkSize;
    $rest = strlen($this->data) - $this->readPos;
    if ($size > $rest)
      $size = $rest;
    return substr($this->data, $this->readPos, $size);
  }
  
//****************************************************************
// Write

  function send(string $data): bool
  {
  }
  
  function close()
  {
  }

//****************************************************************
// Read aync

  private $isReading = null;

  function isReading(): bool { return (bool)$this->isReading; }
  
  function resumeRead(): void
  {
    if ($this->isReading()) throw new LogicException('Unallowed ');
    $this->isReading = true;
    $this->startRead();
  }
  
  protected function startRead()
  {
    Loop::async($this->onRead(...));
  }
  
  protected function onRead()
  {
    if (!$this->isReading()) return; //TODO:
    $this->reader->onData($this);
    if (!$this->isReading()) return;
    $this->startRead();
  }
  
  function pauseRead(): void
  {
    if (!$this->isReading()) throw new LogicException('Unallowed');
    $this->isReading = false;
  }

//****************************************************************
// Write async

  function waitWrite(): void
  {
  }
  
//****************************************************************
};
