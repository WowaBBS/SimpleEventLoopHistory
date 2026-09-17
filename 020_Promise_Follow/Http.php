<?
function httpGetAsync(string $url): Promise
{
  $deferred = new Deferred();
  $parts = parse_url($url);
  $host = $parts['host'];
  $path = $parts['path'] ?? '/';
  $port = $parts['port'] ?? 80;

  // Открываем НЕБЛОКИРУЮЩИЙ сокет
  $fp = stream_socket_client("tcp://$host:$port", $errno, $errstr, 3, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);
  stream_set_blocking($fp, false);

  // Записываем HTTP-запрос (для простоты пишем сразу, в идеале тоже асинхронно через write stream)
  fwrite($fp, "GET $path HTTP/1.1\r\nHost: $host\r\nConnection: close\r\n\r\n");

  $response = '';
  
  // Регистрируем сокет в нашем Event Loop
  Loop::addReadStream($fp, function($stream) use ($deferred, $fp, &$response) {
    $chunk = fread($stream, 8192);
    if ($chunk === '' || $chunk === false)
    {
      // Поток закрылся (данные кончились)
      Loop::removeReadStream($fp);
      fclose($fp);
      $deferred->resolve($response); // Успешно отдаем ответ
    }
    else
      $response .= $chunk;
  });

  return $deferred->promise();
}
