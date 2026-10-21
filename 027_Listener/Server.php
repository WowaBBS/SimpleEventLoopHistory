<?
class AsyncServer
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
      $errno,
      $errstr,
      STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
    );

    if (!$this->serverSocket)
    {
      $deferred->reject(new \RuntimeException("Failed to start server on {$this->address}: $errstr ($errno)"));
      return $deferred->promise();
    }

    stream_set_blocking($this->serverSocket, false);

    // Регистрируем сокет в нашем Цикле Событий для ожидания новых подключений (событие READ)
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

    $clientId = (int)$clientSocket;
    Logger::log("[Client #$clientId] New connection");

    $buffer = '';

    // Регистрируем клиентский сокет на чтение данных
    Loop::addReadStream($clientSocket, function($stream) use ($clientSocket, $clientId, &$buffer) {
      $chunk = fread($stream, 65535);

      if ($chunk === '' || $chunk === false)
      {
        // Клиент закрыл соединение или произошла ошибка
        $this->closeClient($clientSocket, $clientId);
        return;
      }

      $buffer .= $chunk;

      // Для простоты HTTP-сервера: проверяем, долетели ли заголовки полностью (разделитель \r\n\r\n)
      if (str_contains($buffer, "\r\n\r\n"))
      {
        // Перестаем слушать чтение, пока обрабатываем этот запрос
        Loop::removeReadStream($clientSocket);

        // Запускаем обработку запроса в изолированном ФАЙБЕРЕ!
        // Это позволит разработчику внутри обработчика делать await()!
        Loop::async($this->handleClientRequest(...), $clientSocket, $clientId, $buffer);
      }
    });
  }

  /**
   * Асинхронная обработка бизнес-логики внутри файбера
   */
  private function handleClientRequest($clientSocket, int $clientId, string $requestData): void
  {
    try
    {
      // Вызываем пользовательский обработчик, передавая туда сырой запрос.
      // Если внутри обработчика будет вызван await() (например, запрос к БД или файлу),
      // этот файбер замрет, но сервер продолжит принимать других клиентов!
      $responseBody = ($this->handler)($requestData, $clientId);

      // Формируем честный HTTP/1.1 ответ
      $httpResponse = "HTTP/1.1 200 OK\r\n" .
              "Content-Type: text/html; charset=utf-8\r\n" .
              "Content-Length: " . strlen($responseBody) . "\r\n" .
              "Connection: close\r\n\r\n" .
              $responseBody;

      // Записываем ответ клиенту.
      // В идеале запись тоже должна быть через write-стримы Loop, но для базы используем прямую отправку
      @fwrite($clientSocket, $httpResponse);

    }
    catch (\Throwable $e)
    {
      Logger::log("[Client #$clientId] Processing error: " . $e->getMessage());
      $errorResponse = "HTTP/1.1 500 Internal Server Error\r\nConnection: close\r\n\r\nInternal Error";
      @fwrite($clientSocket, $errorResponse);
    }
    finally
    {
      $this->closeClient($clientSocket, $clientId);
    }
  }

  private function closeClient($clientSocket, int $clientId): void
  {
    Loop::removeReadStream($clientSocket);
    if (is_resource($clientSocket))
      fclose($clientSocket);
    
    Logger::log("[Client #$clientId] Connection closed");
  }
}