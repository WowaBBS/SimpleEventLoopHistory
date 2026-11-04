<?
class Stream_Async_LoopBack extends Stream_Async_Base
{
  private IStream_Buffer $buffer ;
  private bool           $isEnd  = false ;
  
  function __construct(string $data = '') { $this->buffer = Stream_Buffer_Factory::create($data, type:'LoopBack'); }
  
  function leakDispose()
  {
    parent::leakDispose();
    $this->buffer = Stream_Buffer_Null::Get();
  }
  
  function getBuffer() { return $this->buffer; }
  
//****************************************************************
// Recv

  function restRecvSize():int { return $this->buffer->restSize(); }
  function hasDataToRecv():bool { return $this->buffer->has(); }
  function recv(int $size): string
  {
    $canSend = $this->canSend();
    $result = $this->buffer->recv($size);
    if (!$canSend && $this->isWriting())
      $this->startSend(true);
    return $result;
  }
  
  function isRecvEnd(): bool { return $this->isEnd && !$this->hasDataToRecv(); }
  
  protected function startRecv(bool $enable)
  {
    if (!$enable) return;
    if (!$this->hasDataToRecv() && !$this->isEnd) return;
    Loop::next($this->handleRead(...));
  }
  
  protected function handleRead(): void
  {
    parent::handleRead();
    if (!$this->isReading()) return;
    if (!$this->hasDataToRecv()) return;
    $this->startRecv(true);
  }

//****************************************************************
// Write

  function canSendSize(): int { return $this->buffer->getFree(); }
  function canSend(): bool { return $this->canSendSize()>0; }

  function send(string $data): false|int
  {
    if ($this->isEnd) return false;
    $len = strlen($data);
    if (!$len) return 0;
    $canSendSize = $this->canSendSize();
    if (!$canSendSize) return 0;
    if ($len > $canSendSize)
    {
      $len = $canSendSize;
      $data = substr($data, 0, $len);
    }
    
  //Logger::debug('send: ',[$data]);
    $hadData = $this->hasDataToRecv();
    $this->buffer->add($data);
    if (!$hadData && $this->isReading())
      $this->startRecv(true);
    return strlen($data);
  }

  function sendEnd(): void
  {
    if ($this->isEnd) return;
    $this->isEnd = true;
    if ($this->hasDataToRecv()) return;
    if (!$this->isReading()) return;
    $this->startRecv(true);
  }

  protected function startSend(bool $enable)
  {
    if (!$enable) return;
    
    if (!$this->canSend() || $this->isEnd) return;
    Loop::next($this->handleWrite(...));
  }
  
//****************************************************************

  function close()
  {
    parent::close();
  }
};
