<?
class Loop_IoWatcher
{
  private array $readStreams  = [];
  private array $writeStreams = []; // Массив сокетов на запись  
  
  // Подписаться на появление данных в сокете/потоке
  function addRead($stream, callable $callback): void
  {
    $this->readStreams[get_resource_id($stream)] = [
      'stream'   => $stream,
      'callback' => $callback
    ];
  }
  
  // Отписаться от потока
  function removeRead($stream): void { unset($this->readStreams[get_resource_id($stream)]); }
  
  /** Новые методы для отслеживания готовности к записи */
  public function addWrite($stream, callable $callback): void
  {
    $this->writeStreams[get_resource_id($stream)] = [
      'stream' => $stream,
      'callback' => $callback
    ];
  }

  public function removeWrite($stream): void { unset($this->writeStreams[get_resource_id($stream)]); }  
  
  /** Блокирующее ожидание системных событий ввода-вывода */
  function watch($timeout)
  {
    if (!$this->readStreams && !$this->writeStreams)
    {
      Stat::$sleep->begin();
      usleep((int)($timeout * 1_000_000)); // Маленький сон, если задач нет, чтобы не перегружать CPU
      Stat::$sleep->end($timeout);
      return;
    }
    
    // Проверяем стримы через stream_select
    $read   = array_column($this->readStreams  ,'stream');
    $write  = array_column($this->writeStreams ,'stream'); // Сокеты на запись
    $except = null;
    
    // Таймаут для select: берем время до ближайшего таймера или 1 секунду
    $tv_sec  = (int)$timeout;
    $tv_usec = (int)(($timeout - $tv_sec) * 1_000_000);

    // Ждем системного уведомления о готовности потоков
    // Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
    // поэтому глушим ее через @ и проверяем статус
    
    Stat::$sleep->begin();
    $count = @stream_select($read, $write, $except, $tv_sec, $tv_usec);
    Stat::$sleep->end($timeout);

    if ($count <= 0) return;
    
    // 1. Проверяем сокеты, готовые к чтению
    foreach ($read as $stream)
    {
      $key = get_resource_id($stream);
      if ($callback = $this->readStreams[$key]['callback']?? null)
        $callback($stream);
    }
    
    // 2. Проверяем сокеты, готовые к записи
    foreach ($write as $stream)
    {
      $key = get_resource_id($stream);
      if ($callback = $this->writeStreams[$key]['callback']?? null)
        $callback($stream);
    }
  }

  function has(): bool
  {
    return count($this->readStreams  )
      ||   count($this->writeStreams );
  }
}
