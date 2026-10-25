<?
/**
 * ЧИСТЫЙ ТРАНСПОРТНЫЙ УРОВЕНЬ (TCP)
 */
class SocketConnection
{
  protected $socket;
  protected int $id;
  /** @var callable */
  private $onDataCallback;

  function __construct($socket, callable $onDataCallback)
  {
    if (!is_resource($socket))
      throw new \InvalidArgumentException('SocketConnection ожидает валидный ресурс сокета.');
  
    $this->socket = $socket;
    $this->id = (int)$socket;
    $this->onDataCallback = $onDataCallback;

    stream_set_blocking($socket, false);
    
    $this->log('New connection');
    
    // Полная инкапсуляция: сокет сам начинает слушать сеть при создании!
    Loop::addReadStream($this->socket, $this->handleRead(...));
  }

  function getId(): int { return $this->id; }
  function getResource() { return $this->socket; }

  /** Внутренний метод чтения данных из сокета */
  private function handleRead($stream): void
  {
    $chunk = fread($stream, 8192);
    if ($chunk === '' || $chunk === false)
    {
      $this->close();
      return;
    }

    // Передаем прочитанный чанк наверх в обработчик (например, в HttpConnection)
    ($this->onDataCallback)($chunk);
  }

  /**
   * Асинхронная неблокирующая запись
   *   с честным Backpressure
   *   с мягкой обработкой обрыва соединения
   */
  function write(string $data): bool
  {
    $length    = strlen($data);
    $written   = 0;
    $wasPaused = false;

    while ($written < $length)
    {
      if (!is_resource($this->socket)) //TODO: ???
        return false; // Сокет уже закрыт
      //throw new \RuntimeException("Attempt to write to a closed socket #{$this->id}");

      // Пытаемся записать оставшийся кусок данных
      $chunk  = substr($data, $written);
      $result = @fwrite($this->socket, $chunk);

      // ШТАТНАЯ ОПЕРАЦИЯ: клиент разорвал соединение посередине отправки.
      // Вместо RuntimeException возвращаем false для мягкого выхода из циклов.
      if ($result === false)
      {
        $this->log('The connection was closed by the client while data was being sent.');
        return false;
      //throw new \RuntimeException("Error writing to client socket #{$this->id}");
      }

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

      // Логика ожидания Writable буфера ОС через Fiber::suspend()
      $currentFiber = \Fiber::getCurrent();
      if ($currentFiber === null)
        throw new \LogicException('Method write can only be called from within a fiber.');

      Loop::addWriteStream($this->socket, function() use ($currentFiber) {
        // Как только сокет готов принимать байты - убираем его из отслеживания записи и будим файбер
        Loop::removeWriteStream($this->socket);
        if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
          $currentFiber->resume();
      });

      // Ставим текущий файбер на паузу
      \Fiber::suspend();
    }
    return true;
  }

  function close(): void
  {
    Loop::removeReadStream($this->socket);
    Loop::removeWriteStream($this->socket);
    if (is_resource($this->socket))
      fclose($this->socket);
    
    $this->log('Connection closed');
  }
  
  /** Удобный метод логирования с префиксом клиента */
  function log(...$args) { return Logger::log('[Client #', $this->id, '] ', ...$args); }
}