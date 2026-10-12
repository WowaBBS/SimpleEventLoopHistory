<?
//TODO: AsyncFileChunk
class FileReaderChunk
{
  /**
   * Асинхронно читает весь файл целиком.
   * Возвращает Promise, который резолвится контентом файла.
   */
  static function readAll(string $filename, int $chunkSize = 8192): Promise
  {
    $deferred = new Deferred();
    $promise = $deferred->promise();

    if (!file_exists($filename))
    {
      $deferred->reject(new \RuntimeException("File not found: $filename"));
      return $promise;
    }

    // Открываем файл на чтение в бинарном режиме
    $stream = @fopen($filename, 'rb');
    if (!$stream)
    {
      $deferred->reject(new \RuntimeException("Can't open the file: $filename"));
      return $promise;
    }
    
    // Переводим в неблокирующий режим (на некоторых ОС влияет на поведение буферов)
    stream_set_blocking($stream, false);
    
    $buffer = '';

    $isAborted = false;

    // Регистрируем логику отмены прямо в промисе!
    $promise->setOnCancel(function() use (&$isAborted, $stream) {
      $isAborted = true;
      if (is_resource($stream))
        fclose($stream);
    });

    // Запускаем рекурсивный процесс чтения через Event Loop
    self::readChunk($stream, $buffer, $chunkSize, $deferred, $isAborted);

    return $promise;
  }

  private static function readChunk($stream, string &$buffer, int $chunkSize, Deferred $deferred, &$isAborted): void
  {
    if ($isAborted) return; // Если была отмена, выходим

    // Проверяем, не достигнут ли конец файла
    if (feof($stream)) 
    {
      fclose($stream);
      $deferred->resolve($buffer);
      return;
    }

    // Читаем один кусочек
    $chunk = fread($stream, $chunkSize);
    
    if ($chunk === false)
    {
      fclose($stream);
      $deferred->reject(new \RuntimeException("Error reading file"));
      return;
    }    
    
    $buffer .= $chunk;

    // Вместо того чтобы сразу читать следующий кусок в цикле (что заблокирует поток),
    // мы откладываем чтение следующего чанка на следующий "тик" Loop.
    // Таймаут 0 означает: "выполни как можно скорее, но дай сначала подышать другим задачам".
    Loop::delay(0, function() use ($stream, &$buffer, $chunkSize, $deferred, &$isAborted) {
      self::readChunk($stream, $buffer, $chunkSize, $deferred, $isAborted);
    });
  }
}