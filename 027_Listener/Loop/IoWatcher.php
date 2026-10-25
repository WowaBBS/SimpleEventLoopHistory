<?
class Loop_IoWatcher
{
  private array $readStreams= [];
  
  // Подписаться на появление данных в сокете/потоке
  function addRead($stream, callable $callback): void
  {
    $this->readStreams[get_resource_id($stream)] = [
      'stream'   => $stream,
      'callback' => $callback
    ];
  }
  
  // Отписаться от потока
  function removeRead($stream): void
  {
    unset($this->readStreams[get_resource_id($stream)]);
  }
  
  /** Блокирующее ожидание системных событий ввода-вывода */
  function watch($timeout)
  {
    if (!$this->readStreams)
    {
      Stat::$sleep->begin();
      usleep((int)($timeout*1000000)); // Маленький сон, если задач нет, чтобы не перегружать CPU
      Stat::$sleep->end($timeout);
      return;
    }
    
    // Проверяем стримы через stream_select
    $read   = array_column($this->readStreams, 'stream');
    $write  = null;
    $except = null;
    
    // Таймаут для select: берем время до ближайшего таймера или 1 секунду
    $tv_sec  = (int)$timeout;
    $tv_usec = (int)(($timeout - $tv_sec) * 1000000);

    // Ждем системного уведомления о готовности потоков
    // Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
    // поэтому глушим ее через @ и проверяем статус
    
    Stat::$sleep->begin();
    $count = @stream_select($read, $write, $except, $tv_sec, $tv_usec);
    Stat::$sleep->end($timeout);

    if ($count > 0)
      foreach ($read as $stream)
      {
        $key = get_resource_id($stream);
        if (isset($this->readStreams[$key]))
          ($this->readStreams[$key]['callback'])($stream);
      }
  }

  function has(): bool { return count($this->readStreams); }
}
