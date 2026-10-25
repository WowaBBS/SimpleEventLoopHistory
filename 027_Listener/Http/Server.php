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
  public function listen(): Promise
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

    Logger::log("Server successfully started and is listening: {$this->address}");
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
    $connection = new HttpConnection($clientSocket);
    $connection->log('New connection');

    $buffer = '';
    // Регистрируем клиентский сокет на чтение данных
    Loop::addReadStream($clientSocket, function($stream) use ($connection, &$buffer) {
      $chunk = fread($stream, 8192);

      if ($chunk === '' || $chunk === false)
      { // Клиент закрыл соединение или произошла ошибка
        $connection->close();
        return;
      }

      $buffer .= $chunk;

      // Для простоты HTTP-сервера: проверяем, долетели ли заголовки полностью (разделитель \r\n\r\n)
      if (!str_contains($buffer, "\r\n\r\n")) return;
      
      // Перестаем слушать чтение, пока обрабатываем этот запрос
      Loop::removeReadStream($stream);

      // Запускаем обработку запроса в изолированном ФАЙБЕРЕ!
      // Это позволит разработчику внутри обработчика делать await()!
      Loop::async($this->handleClientRequest(...), $connection, $buffer);
    });
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
      $responseBody = ($this->handler)($requestData, $connection);
      
      if ($responseBody === true) return;
      if (is_string($responseBody))
        $connection->sendResponse(200, 'Ok', $responseBody);
      else
        $connection->sendResponse(404, 'Not Found', 'Not Found');
    }
    catch (\Throwable $e)
    {
      $connection->log('Processing error')->exception($e);
      $connection->sendResponse(500, 'Internal Server Error', 'Internal Error');
    }
    finally
    {
      $connection->close();
    }
  }
}