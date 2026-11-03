<?
class Stream_SyncSimple implements IStream
{
  private IStream_Async $stream     ;
  private string        $readBuffer = '';
  private bool          $isRecvEnd  = false;

  public function __construct(IStream_Async $stream)
  {
    $this->stream = $stream;
  }

  // --- СЕКЦИЯ ЗАПИСИ (WRITE) ---

  public function write(...$args): int 
  {
    $totalWritten = 0;
    foreach ($args as $arg)
      if (is_array($arg))
        $totalWritten += $this->write(...$arg);
      else
        $totalWritten += $this->writeData((string)$arg);
    
    return $totalWritten;
  }

  public function writeData(string $data): int
  {
    $length = strlen($data);
    $written = 0;

    while ($written < $length)
    {
      $chunk = substr($data, $written);
      $result = $this->stream->send($chunk);

      // Если буфер ОС переполнен (вернулся false), подписываемся на resumeSend и спим
      if ($result === false)
      {
        $currentFiber = \Fiber::getCurrent();
        if ($currentFiber === null)
          throw new \LogicException("Запись в заблокированный поток возможна только внутри Fiber.");

        $this->stream->resumeSend(function() use ($currentFiber) {
          $this->stream->pauseSend();
          if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
            $currentFiber->resume();
        });

        \Fiber::suspend();
        continue;
      }

      if ($result > 0)
        $written += $result;
      else // Если вернулось 0 без ошибки, даем тику Loop пройти
        sleepAsync(0)->await();
    }

    return $written;
  }

  public function writeEnd(): void { $this->stream->sendEnd(); }

  // --- СЕКЦИЯ ЧТЕНИЯ (READ) ---

  public function waitData(): bool { return $this->readBuffer !== '' || $this->waitMore(); }
  
  public function waitMore(): bool
  {
    if ($this->isRecvEnd || $this->stream->isRecvEnd())  // TODO: Duplicate isRecvEnd
    {
      $this->isRecvEnd = true;
      return false;
    }

    $currentFiber = \Fiber::getCurrent();
    if ($currentFiber === null)
      throw new \LogicException("Метод waitMore() можно вызывать только внутри Fiber.");

    // TODO: Promise
    // Подписываемся на чтение данных из асинхронного стрима
    $this->stream->resumeRecv(function() use ($currentFiber) {
      $this->stream->pauseRecv();

      // Вычитываем то, что пришло в сокет
      $chunk = $this->stream->recv(65536);
      if ($chunk === null || $chunk === '')
        $this->isRecvEnd = true;
      else
        $this->readBuffer .= $chunk;

      if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
        $currentFiber->resume();
    });

    \Fiber::suspend();
    return !$this->isRecvEnd;
  }

  public function readData(int $size, ?int $min = null): string
  {
    while (strlen($this->readBuffer) < $size)
      if (!$this->waitMore())
      { // Достигли конца потока
        if ($min !== null && strlen($this->readBuffer) >= $min)
        {
          $result = $this->readBuffer;
          $this->readBuffer = '';
          return $result;
        }
        throw new \UnderflowException("Неожиданный конец данных: затребовано $size байт, получено " . strlen($this->readBuffer));
      }

    $result = substr($this->readBuffer, 0, $size);
    $this->readBuffer = substr($this->readBuffer, $size);
    return $result;
  }

  public function peek(int $min = 1, ?int $max = null): string
  {
    $max = $max ?? $min;

    while (strlen($this->readBuffer) < $min)
      if (!$this->waitMore())
        break; // Больше данных не будет, отдаем то, что есть

    return substr($this->readBuffer, 0, min(strlen($this->readBuffer), $max));
  }

  public function unread(string $data, bool $new = false): void
  { // Возвращаем строго то, что читали ранее
    $this->readBuffer = $data . $this->readBuffer;
  }

  public function skip(int $size): void { $this->readData($size, $size); }

  public function readLine(string $till = "\n", bool $strict = false): string 
  {
    $pos = strpos($this->readBuffer, $till);

    while ($pos === false)
    {
      if (!$this->waitMore()) 
      {
        if ($strict)
          throw new \RuntimeException("Стриггер окончания строки '$till' не найден до конца потока.");
        
        $result = $this->readBuffer;
        $this->readBuffer = '';
        return $result;
      }
      $pos = strpos($this->readBuffer, $till);
    }

    $tillLen = strlen($till);
    $line = substr($this->readBuffer, 0, $pos);
    $this->readBuffer = substr($this->readBuffer, $pos + $tillLen); // Скипаем сам $till

    return $line;
  }

  public function read(...$args): array|string
  {
    if (empty($args)) return $this->readData(1, 0);
    
    return $this->readData((int)$args[0], $args[1] ?? null);
  }

  function until(): Stream_Until_Factory { return new Stream_Until_Factory($this); }
  
  public function close(): void
  {
    $this->readBuffer = '';
    $this->stream->close();
  }
//****************************************************************
// TODO: Optimize

  function asyncTask(callable $ioFunc):Promise
  {
    $deferred = new Deferred();
    Loop::async(function() use($deferred, $ioFunc){
      try
      {
        while (1)
        {
          $result = $ioFunc($this);
          
          if (Stream_Stack_Base::isFinished($result))
            return $deferred->resolve($result);
          
          $this->waitMore();
        }
      }
      catch(\Throwable $e)
      {
        $deferred->reject($e);
      }
    });

    return $deferred->Promise();
  }
  
  function require(int $bytes, ?int $expect=null) {}
  function isStreamEnd() { return $this->isRecvEnd; }
  function buffer_readAll() { $result=$this->readBuffer; $this->readBuffer=''; return $result; }
  function buffer_until    (callable $find, ?bool $include=null): false|string
  {
    $pos = $find($this->readBuffer, 0);
    if ($pos === false) return false;
    [$pos, $end] = $pos;
    if ($include === true) $pos += strlen($end);
    $result = $this->read($pos);
    if ($include === null) $this->skip(strlen($end));
    return $result;
  }

//****************************************************************
}