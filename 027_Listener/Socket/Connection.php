<?
class SocketConnection
{
  protected $socket;
  protected int $id;

  public function __construct($socket)
  {
    if (!is_resource($socket))
      throw new \InvalidArgumentException('SocketConnection ожидает валидный ресурс сокета.');
    
    $this->socket = $socket;
    $this->id = (int)$socket;
  }

  public function getId(): int { return $this->id; }
  public function getResource() { return $this->socket; }

  /**
   * Асинхронная неблокирующая запись в сокет с честным Backpressure
   */
  public function write(string $data): void
  {
    $length    = strlen($data);
    $written   = 0;
    $wasPaused = false;

    while ($written < $length)
    {
      if (!is_resource($this->socket))
        throw new \RuntimeException("Attempt to write to a closed socket #{$this->id}");

    // Пытаемся записать оставшийся кусок данных
      $chunk  = substr($data, $written);
      $result = @fwrite($this->socket, $chunk);

      if ($result === false)
        throw new \RuntimeException("Error writing to client socket #{$this->id}");

      if ($result > 0)
      {
        $written += $result;
        if ($wasPaused)
        {
          $this->log('The network buffer has become available. Sending has resumed.');
          $wasPaused = false;
        }
        continue;
      }

      // Если записалось 0 байт - сетевой буфер ОС переполнен!
      if (!$wasPaused)
      {
        $this->log('Network buffer full! Sending temporarily paused...');
        $wasPaused = true;
      }

      // Ставим текущий файбер на паузу
      $currentFiber = \Fiber::getCurrent();
      if ($currentFiber === null)
        throw new \LogicException('write can only be called from within a fiber.');

      Loop::addWriteStream($this->socket, function() use ($currentFiber) {
        // Как только сокет готов принимать байты - убираем его из отслеживания записи и будим файбер
        Loop::removeWriteStream($this->socket);
        if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
          $currentFiber->resume();
      });

      \Fiber::suspend();
    }
  }

  public function close(): void
  {
    Loop::removeReadStream($this->socket);
    Loop::removeWriteStream($this->socket);
    if (is_resource($this->socket))
      fclose($this->socket);
    
    $this->log('Connection closed');
  }
  
  function log(...$args) { return Logger::log('[Client #', $this->id, '] ', ...$args); }
}