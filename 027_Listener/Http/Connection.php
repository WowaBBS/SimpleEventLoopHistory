<?
class HttpConnection extends SocketConnection
{
  private bool $headersSent = false;

  /**
   * Отправляет HTTP-заголовки
   */
  public function sendHeaders(int $statusCode, string $statusText, array $headers = []): void
  {
    if ($this->headersSent)
      throw new \LogicException("Заголовки для клиента #{$this->id} уже были отправлены.");

    // Формируем честный HTTP/1.1 ответ
    $response = "HTTP/1.1 $statusCode $statusText\r\n";
    foreach ($headers as $name => $value)
      $response .= "$name: $value\r\n";
    
    $response .= "\r\n";

    // Записываем ответ клиенту.
    // В идеале запись тоже должна быть через write-стримы Loop, но для базы используем прямую отправку
    $this->write($response);
    $this->headersSent = true;
  }

  /**
   * Отправляет полноценный атомарный HTTP-ответ
   */
  public function sendResponse(int $statusCode, string $statusText, string $body, string $contentType = 'text/html; charset=utf-8'): void 
  {
    $headers = [
      'Content-Type'   => $contentType,
      'Content-Length' => strlen($body),
      'Connection'     => 'close'
    ];

    $this->sendHeaders($statusCode, $statusText, $headers);
    $this->write($body);
  }

  /**
   * Отправляет порцию данных при стриминге больших файлов
   */
  public function sendChunk(string $data): void
  {
    if (!$this->headersSent)
      throw new \LogicException('Нельзя отправлять данные без предварительной отправки заголовков.');
    
    // Используем базовый асинхронный метод записи с Backpressure
    $this->write($data);
  }
}