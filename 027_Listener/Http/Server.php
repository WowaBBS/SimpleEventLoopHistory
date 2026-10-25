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
    
    stream_set_blocking($clientSocket, false);

    // Создаем объект-обёртку для HTTP протокола
    new HttpConnection($clientSocket, $this->onClientRequestReady(...));
  }

  /** Вызывается из HttpConnection, когда http-запрос полностью вычитан из сети */
  private function onClientRequestReady(HttpConnection $connection, string $requestData): void
  {
    Loop::async($this->handleClientRequest(...), $connection, $requestData);
  }

  /**
   * Асинхронная обработка бизнес-логики внутри файбера
   */
  private function handleClientRequest(HttpConnection $connection, string $requestData): void
  {
    try
    {
      // Вызываем пользовательский обработчик, передавая туда сырой запрос.
      // Если внутри обработчика будет вызван await() (например, запрос к БД или файлу),
      // этот файбер замрет, но сервер продолжит принимать других клиентов!
      $result = ($this->handler)($requestData, $connection);
      
      // --- УМНЫЙ РОУТИНГ ПО РЕЗУЛЬТАТУ ВЫПОЛНЕНИЯ ХЕНДЛЕРА ---      
      
      // Сценарий 1: Хендлер сам отправил все данные (например, /bigfile)
      if ($result === true) return;
      if (is_string($result)) // Сценарий 2: Хендлер просто вернул строку - отдаем её со статусом 200
        $connection->sendResponse(200, 'Ok', $result);
      else // Сценарий 3: Страница не найдена - отдаем красивую 404
        $connection->sendResponse(404, 'Not Found', '<h1>Page not found</h1>');
    }
    catch (\Throwable $e)
    {
      $connection->log('Processing error')->exception($e);
      try { $connection->sendResponse(500, 'Internal Server Error', '<h1>Internal Server Error</h1>'); } catch (\Throwable) {}
    }
    finally
    {
      $connection->close();
    }
  }
}