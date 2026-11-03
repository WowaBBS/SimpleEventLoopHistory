<?
class HttpServer
{
  private $serverSocket;
  private string $address;
  /** @var callable */
  private $handler;

  function __construct(string $address, callable $handler)
  {
    $this->address = $address;
    $this->handler = $handler;
  }

  /**
   * Запуск сервера. Метод возвращает Promise, который резолвится при старте,
   * но сам сервер продолжает крутиться в фоне внутри Event Loop.
   */
  function listen(): Promise
  {
    $deferred = new Deferred();

    // Открываем НЕБЛОКИРУЮЩИЙ серверный сокет
    $this->serverSocket = @stream_socket_server(
      $this->address,
      $errno, $errstr,
      STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
    );

    if (!$this->serverSocket)
    {
      $deferred->reject(new \RuntimeException("Failed to start server on {$this->address}: $errstr ($errno)"));
      return $deferred->promise();
    }

    stream_set_blocking($this->serverSocket, false);

    // Листенер ждет новые соединения через IoWatcher (Read-событие на серверном сокете)
    Loop::addReadStream($this->serverSocket, $this->acceptConnection(...));

    Logger::log("TCP/HTTP server successfully started and is listening: {$this->address}");
    $deferred->resolve($this);

    return $deferred->promise();
  }

  /**
   * Метод вызывается Loop-ом, когда к серверу кто-то подключается
   */
  private function acceptConnection($serverSocket): void
  {
    $clientSocket = @stream_socket_accept($serverSocket, 0); // 0 - таймаут мгновенно
    if (!$clientSocket) return;
    
    // 1. Создаем асинхронный транспорт (Реализует IStream_Async)
    $asyncTransport = new SocketConnection($clientSocket);
    $clientId = $asyncTransport->getId();
    
    // 2. Оборачиваем его в синхронный стрим (Реализует IStream)
    $syncStream = $asyncTransport->syncStream(); //new Pipeline_Stream_Sync($asyncTransport);
    
    $transport = new SocketConnection($clientSocket);

    // 3. Передаем синхронный стрим в HttpConnection, который будет работать с ним в Файбере
    new HttpConnection($syncStream, $clientId, $this->handler);
  }
}