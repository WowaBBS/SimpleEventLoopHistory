<?
/**
 * ПРИКЛАДНОЙ УРОВЕНЬ ПРОТОКОЛА (HTTP)
 */
class HttpConnection
{
  private IStream $stream;
  private int $id;
  private bool $headersSent = false;
  
  function __construct(IStream $stream, int $id, callable $handler)
  {
    $this->stream = $stream;
    $this->id = $id;

    // Запускаем синхронную обработку в изолированном файбере
    Loop::async($this->handleSession(...), $handler);
  }
  
  /**
   * Асинхронная обработка бизнес-логики внутри файбера
   */
  private function handleSession(callable $handler): void
  {
    try
    {
      // 1. Читаем стартовую строку HTTP-запроса синхронно!
      // readLine автоматически вырежет "\r\n" на конце
      $requestLine = $this->stream->readLine("\r\n");
      if (empty($requestLine)) return;

      // Парсим первую строчку HTTP запроса (например: GET /index.html HTTP/1.1)
      preg_match('/GET\s+(\/[^\s?]*)/', $requestLine, $matches);
      $path = $matches[1] ?? '/';

      // 2. Читаем все заголовки построчно, пока не встретим пустую строку
      $headers =[];
      while (($line = $this->stream->readLine("\r\n")) !== "")
        $headers[] = $line;
      
      // 2. Ищем заголовок Content-Length, чтобы понять, сколько байт тела нужно прочесть
      $contentLength = null;
      foreach ($headers as $header)
        if (stripos($header, 'Content-Length:') === 0)
          $contentLength = (int)trim(substr($header, 15));
      
      if ($contentLength !== null)
        $body = $stream->read($contentLength);
      else
        $body = null;
      
      // 3. Вызываем пользовательский хендлер бизнес-логики
      $result = $handler($path, $this);

      // 4. Умный роутинг по результату ответа
      if ($result === true) return;// Сценарий 1: Хендлер сам отправил все данные (например, /bigfile)
      if (is_string($result)) // Сценарий 2: Хендлер просто вернул строку - отдаем её со статусом 200
        $this->sendResponse(200, 'Ok', $result);
      else // Сценарий 3: Страница не найдена - отдаем красивую 404
        $this->sendResponse(404, 'Not Found', '<h1>Page not found</h1>');
    }
    catch (\Throwable $e)
    {
      $this->log('Processing error')->exception($e);
      try { $this->sendResponse(500, 'Internal Server Error', '<h1>Internal Server Error</h1>'); } catch (\Throwable) {}
    }
    finally
    {
      $this->stream->close();
    }
  }

  /**
   * Отправляет HTTP-заголовки
   */
  function sendHeaders(int $statusCode, string $statusText, array $headers = []): bool
  {
    if ($this->headersSent)
      throw new \LogicException("Заголовки для клиента #{$this->id} уже были отправлены.");

    // Формируем честный HTTP/1.1 ответ
    $response = "HTTP/1.1 $statusCode $statusText\r\n";
    foreach ($headers as $name => $value)
      $response .= "$name: $value\r\n";
    $response .= "\r\n";

    $this->headersSent = true;
    // Записываем ответ клиенту.
    // В идеале запись тоже должна быть через write-стримы Loop, но для базы используем прямую отправку
    return $this->stream->write($response); // Делегируем запись транспорту
  }

  /**
   * Отправляет полноценный атомарный HTTP-ответ
   */
  function sendResponse(int $statusCode, string $statusText, string $body, string $contentType = 'text/html; charset=utf-8'): bool
  {
    $headers = [
      'Content-Type'   => $contentType,
      'Content-Length' => strlen($body),
      'Connection'     => 'close',
    ];

    if (!$this->sendHeaders($statusCode, $statusText, $headers))
      return false;
    
    return $this->stream->write($body);
  }
  
  /**
   * Отправляет кусок файла. Если вернулось false (обрыв),
   * вызывающий код (цикл генерации файла) должен сразу выйти.
   */
  function sendChunk(string $data): bool
  {
    if (!$this->headersSent)
      throw new \LogicException("Нельзя отправлять данные без заголовков.");
    
    // Используем базовый асинхронный метод записи с Backpressure
    return $this->stream->write($data);
  }

  /** Проксируем ID и Логгер наружу для удобства хендлера */
  function getId(): int { return $this->id; }
  function close(): void { $this->stream->close(); }
  function log(...$args) { return Logger::log('[Client #', $this->id, '] ', ...$args); }
}