<?
/**
 * ЧИСТЫЙ ТРАНСПОРТНЫЙ УРОВЕНЬ (TCP)
 */
class SocketConnection extends Stream_Async_Base
{
  private $socket;
  private int $id;
  private bool $isRecvEnd = false;
  private bool $isSendEnd = false;
  
  function __construct($socket)
  {
    $this->socket = $socket;
    if (!$this->isValid())
      throw new \InvalidArgumentException('SocketConnection ожидает валидный ресурс сокета.');
  
    $this->id = (int)$socket;

    // Гарантируем неблокирующий режим работы сокета на уровне ОС
    stream_set_blocking($socket, false);
    
    $this->log('New connection');
  }
  
  function isValid(): bool { return is_resource($this->socket); }
  function getId(): int { return $this->id; }

  // --- ИМПЛЕМЕНТАЦИЯ ЧТЕНИЯ (RECV) ---

  function recv(int $maxSize): ?string
  {
    if (!$this->isValid() || $this->isRecvEnd)
      return null;

    $chunk = fread($this->socket, $maxSize);

    // В неблокирующем PHP fread возвращает false, если данных в буфере прямо сейчас нет,
    // и пустую строку '', если удаленная сторона закрыла соединение (FIN пакет)
    if ($chunk === false) return ''; // Данных нет, но поток жив

    if ($chunk === '')
    {
      $this->isRecvEnd = true;
      return null; // Поток для чтения закрыт удаленной стороной
    }

    return $chunk;
  }

  function isRecvEnd(): bool { return $this->isRecvEnd; }
  
  protected function startRecv(bool $enable)
  {
    if ($enable)// Подписываем сокет на события чтения в нашем Loop
      Loop::addReadStream($this->socket, $this->handleRead(...));
    else
      Loop::removeReadStream($this->socket);
  }
  
  private bool $wasPaused = false;

  // --- ИМПЛЕМЕНТАЦИЯ ЗАПИСИ (SEND) ---
  
  function send(string $data): false|int
  {
    if (!$this->isValid() || $this->isSendEnd)
      return false;

    $result = @fwrite($this->socket, $data);

    // Если клиент жестко оборвал соединение (RST пакет), fwrite вернет false
    if ($result === false)
    {
      $this->isSendEnd = true;
      $this->log('The connection was closed by the client while data was being sent.');
      return false;
    }

    // Если вернулось 0 - сетевой буфер ОС переполнен, запись заблокирована
    if ($result === 0)
    {
      if (!$this->wasPaused)
      {
        $this->log('Network buffer full! Sending temporarily paused...');
        $this->wasPaused = true;
      }
      return false; //TODO: 0 or false
    }

    if ($this->wasPaused)
    {
      $this->log('The network buffer has become available. Sending has resumed.');
      $this->wasPaused = false;
    }
    
    return $result;
  }

  function sendEnd(): void
  {
    if ($this->isSendEnd) return;
    $this->isSendEnd = true;

    if (!$this->isValid()) return;
    
    // Частично закрываем сокет только на отправку (FIN), оставляя чтение живым
    stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
  }

  function startSend(bool $enable)
  {
    if ($enable) // Подписываем сокет на события готовности к записи (Writable) в Loop
      Loop::addWriteStream($this->socket, $this->handleWrite(...));
    else
      Loop::removeWriteStream($this->socket);
  }

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

      $this->resumeSend(function() use ($currentFiber) {
        // Как только сокет готов принимать байты - убираем его из отслеживания записи и будим файбер
        if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
          $currentFiber->resume();
      });

      // Ставим текущий файбер на паузу
      \Fiber::suspend();
      
      $socket->pauseSend();
    }
    return true;
  }

  // --- ЗАКРЫТИЕ РЕСУРСОВ ---

  function close(): void
  {
    parent::close();
    $this->isRecvEnd = true;
    $this->isSendEnd = true;

    if ($this->isValid())
      fclose($this->socket);
    
    $this->log('Connection closed');
  }
  
  /** Удобный метод логирования с префиксом клиента */
  function log(...$args) { return Logger::log('[Client #', $this->id, '] ', ...$args); }
}