<?
/**
 * ПРИКЛАДНОЙ УРОВЕНЬ ПРОТОКОЛА (HTTP)
 */
class HttpConnection
{
  private SocketConnection $transport;
  private string $buffer = '';
  private bool $headersSent = false;
  /** @var callable */
  private $requestReadyCallback;

  function __construct(SocketConnection $transport, callable $requestReadyCallback)
  {
    $this->requestReadyCallback = $requestReadyCallback;

    // Передаем SocketConnection самого себя в качестве фабрики,
    // внедряя колбэк накопления буфера HTTP
    $this->transport = $transport;
    $transport->resumeRead($this->appendBuffer(...));
  }

  private function appendBuffer(string $chunk): void
  {
    $this->buffer .= $chunk;

    // Для простоты HTTP-сервера: проверяем, долетели ли заголовки полностью (разделитель \r\n\r\n)
    if (!str_contains($this->buffer, "\r\n\r\n")) return;
    
    // Заголовки получены полностью. Снимаем сокет с чтения, чтобы не читать лишнего
    Loop::removeReadStream($this->transport->getResource());

    // Вызываем колбэк сервера, передавая накопленный запрос
    ($this->requestReadyCallback)($this, $this->buffer);
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
    return $this->transport->write($response); // Делегируем запись транспорту
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
    
    return $this->transport->write($body);
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
    return $this->transport->write($data);
  }

  /** Проксируем ID и Логгер наружу для удобства хендлера */
  function getId(): int { return $this->transport->getId(); }
  function close(): void { $this->transport->close(); }
  function log(mixed ...$args): void { $this->transport->log(...$args); }
}