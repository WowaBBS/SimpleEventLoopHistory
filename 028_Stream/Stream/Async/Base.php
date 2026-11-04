<?
abstract class Stream_Async_Base implements IStream_Async, IDebug_Leak_Object
{
  function syncStream() { return new Stream_Sync($this); }
//function syncStream() { return new Stream_SyncSimple($this); }
  
  function __construct () { Debug_Leak_Tracker::register($this); }
  
  function leakDispose()
  {
    $this->recvCallback = null;
    $this->sendCallback = null;
  }

//****************************************************************
// Recv
  /** @var callable|null Колбэк на чтение данных */
  private $recvCallback = null;

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
  
//****************************************************************
// Send

  /** @var callable|null Колбэк на готовность к записи */
  private $sendCallback = null;

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

  abstract protected function startSend(bool $enable);

//****************************************************************

  /**
   * Асинхронная неблокирующая запись
   *   с честным Backpressure
   *   с мягкой обработкой обрыва соединения
   */
  function write(string $data): bool
  { //TODO: remove?
    $length    = strlen($data);
    $written   = 0;

    while ($written < $length)
    {
      if (!$this->isValid()) //TODO: ???
        return false; // Сокет уже закрыт
      //throw new \RuntimeException("Attempt to write to a closed socket #{$this->id}");

      // Пытаемся записать оставшийся кусок данных
      $chunk  = substr($data, $written);
      $result = $this->send($chunk);

      // ШТАТНАЯ ОПЕРАЦИЯ: клиент разорвал соединение посередине отправки.
      // Вместо RuntimeException возвращаем false для мягкого выхода из циклов.
      if ($result === false)
        if ($this->isSendEnd)
          return false;
        else
          continue;
          
      $written += $result;
      
      if ($result !== false)
        continue;
      
      // Логика ожидания Writable буфера ОС через Fiber::suspend()
      $currentFiber = \Fiber::getCurrent();
      if ($currentFiber === null)
        throw new \LogicException('Method write can only be called from within a fiber.');

      $this->resumeSend(function($stream) use ($currentFiber) {
        $stream->pauseSend();
        // Как только сокет готов принимать байты - убираем его из отслеживания записи и будим файбер
        if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
          $currentFiber->resume();
      });

      // Ставим текущий файбер на паузу
      \Fiber::suspend();
    }
    return true;
  }

  function isValid(): bool { return true; }
  
  function close()
  {
    $this->recvCallback = null;
    $this->sendCallback = null;
    
    // Полностью вычищаем сокет из системного селектора Loop
    $this->startRecv(false);
    $this->startSend(false);
  }

}