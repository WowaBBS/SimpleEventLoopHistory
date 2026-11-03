<?
abstract class Stream_Async_Base implements IStream_Async, IDebug_Leak_Object
{
//function syncStream() { return new Stream_Sync($this); }
  function syncStream() { return new Stream_SyncSimple($this); }
  
  function __construct () { Debug_Leak_Tracker::register($this); }
  
  function leakDispose()
  {
    $this->recvCallback = null;
    $this->sendCallback = null;
  }

  /** @var callable|null Колбэк на чтение данных */
  private $recvCallback = null;

  /** @var callable|null Колбэк на готовность к записи */
  private $sendCallback = null;

  function isReading(): bool { return (bool)$this->recvCallback; }

  /**
   * Включает неблокирующее чтение из сокета.
   * Переданный колбэк будет вызываться каждый раз, когда прилетают сырые байты.
   */
  final function resumeRecv($callback): void
  {
  //Logger::debug('resumeRecv');
    if ($this->isReading())
    { 
      Logger::error('Unallowed resumeRecv twice')->backTrace();
      $this->recvCallback = $callback; //TODO: Check equals
    }
    else
    {
      $this->recvCallback = $callback;
      $this->startRecv(true);
    }
  }
  
  /** Выключает чтение из сокета (снимает нагрузку с IoWatcher) */
  final function pauseRecv(): void
  {
  //Logger::debug('pauseRecv');
    if (!$this->isReading()) Logger::error('Unallowed pauseRecv twice')->backTrace();
    $this->recvCallback = null;
    $this->startRecv(false);
  }
  
  /** Внутренний метод чтения данных из сокета */
  protected function handleRead(): void
  {
    if ($this->recvCallback)
      ($this->recvCallback)($this);
    else
      Logger::error('Readed data withhout a handler')->backtrace(); //TODO: It's inpossible
  }

  abstract protected function startRecv(bool $enable);
  

  function isWriting(): bool { return (bool)$this->sendCallback; }

  final function resumeSend($callabck): void
  {
  //Logger::debug('resumeSend');
    if ($this->isWriting())
    {
      Logger::error('Unallowed resumeSend twice')->backTrace();
      $this->sendCallback = $callabck; //TODO: Check equals
    }
    else
    {
      $this->sendCallback = $callabck;
      $this->startSend(true);
    }
  }
  
  final function pauseSend(): void
  {
  //Logger::debug('pauseSend');
    if (!$this->isWriting()) 
      Logger::error('Unallowed pauseSend twice')->backTrace();
      
    $this->sendCallback = null;
    $this->startSend(false);
  }
  
  protected function handleWrite(): void
  {
    if (!$this->isWriting())
      Logger::error('Unallowed onSend without resumeSend')->backTrace();
    else
      ($this->sendCallback)($this);
  }

  abstract function startSend(bool $enable);

  function close()
  {
    $this->recvCallback = null;
    $this->sendCallback = null;
    
    // Полностью вычищаем сокет из системного селектора Loop
    $this->startRecv(false);
    $this->startSend(false);
  }

}