<?
class Stream_Async_LoopBack extends Stream_Async_Base
{
  private IStream_Buffer $buffer ;
  private bool           $isEnd  = false ;
  
  function __construct(string $data = '') { $this->buffer = new Stream_Buffer_String($data); }
  
  function leakDispose()
  {
    parent::leakDispose();
    $this->onRecv = null;
    $this->onSend = null;
    $this->buffer = Stream_Buffer_Null::Get();
  }
  
  function getBuffer() { return $this->buffer; }
  
//****************************************************************
// Recv
  private $onRecv = null;

  function restRecvSize():int { return $this->buffer->restSize(); }
  function hasDataToRecv():bool { return $this->buffer->has(); }
  function recv(int $size): string
  {
    $canSend = $this->canSend();
    $result = $this->buffer->read($size);
    if (!$canSend && $this->isWriting())
      $this->startSend();
    return $result;
  }
  
  function isRecvEnd(): bool { return $this->isEnd && $this->hasDataToRecv(); }
  
  function isReading(): bool { return (bool)$this->onRecv; }
  
  function resumeRecv($callback): void
  {
  //Logger::debug('resumeRecv');
    if ($this->isReading())
    { 
      Logger::error('Unallowed resumeRecv twice')->backTrace();
      $this->onRecv = $callback; //TODO: Check equals
    }
    else
    {
      $this->onRecv = $callback;
      $this->startRecv();
    }
  }
  
  function pauseRecv(): void
  {
  //Logger::debug('pauseRecv');
    if (!$this->isReading()) Logger::error('Unallowed pauseRecv twice')->backTrace();
    $this->onRecv = null;
  }
  
  protected function startRecv()
  {
    if (!$this->hasDataToRecv() && !$this->isEnd) return;
    Loop::next($this->onRecv(...));
  }
  
  protected function onRecv()
  {
    if (!$this->isReading()) return; //TODO: Logger::error('Unallowed onRecv without resumeRecv')->backTrace();
    ($this->onRecv)($this);
    if (!$this->isReading()) return;
    if (!$this->hasDataToRecv()) return;
    $this->startRecv();
  }

//****************************************************************
// Write

  private $onSend = null;
  
  function isWriting(): bool { return (bool)$this->onSend; }
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
      $this->startRecv();
    return strlen($data);
  }

  function sendEnd(): void
  {
    if ($this->isEnd) return;
    $this->isEnd = true;
    if ($this->hasDataToRecv()) return;
    if (!$this->isReading()) return;
    $this->startRecv();
  }

  function resumeSend($callabck): void
  {
  //Logger::debug('resumeSend');
    if ($this->isWriting())
    {
      Logger::error('Unallowed resumeSend twice')->backTrace();
      $this->onSend = $callabck; //TODO: Check equals
    }
    else
    {
      $this->onSend = $callabck;
      $this->startSend();
    }
  }
  
  function startSend()
  {
    if (!$this->canSend() || $this->isEnd) return;
    Loop::next($this->onSend(...));
  }
  
  function pauseSend(): void
  {
  //Logger::debug('pauseSend');
    if (!$this->isWriting()) 
      Logger::error('Unallowed pauseSend twice')->backTrace();
      
    $this->onSend = null;
  }
  
  function onSend()
  {
    if (!$this->isWriting())
      Logger::error('Unallowed onSend without resumeSend')->backTrace();
    else
      ($this->onSend)($this);
  }
  
//****************************************************************

  function close()
  {
  }
};
