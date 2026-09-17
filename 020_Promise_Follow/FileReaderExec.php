<?
class FileReaderExec
{
  private $process;
  private $pipes = [];
//private $chunkSize;
  private $deferred;

  /**
   * Асинхронно читает весь файл целиком.
   * Возвращает Promise, который резолвится контентом файла.
   */
  static function readAll(string $filename, int $chunkSize = 8192): Promise
  {
    // Запускаем рекурсивный процесс чтения через Event Loop
    $Reader = new self($filename, $chunkSize);
    
    return $Reader->deferred->promise();
  }
  
  function __construct(string $filename, int $chunkSize = 8192)
  {
    $this->deferred = new Deferred();
  //$this->chunkSize = $chunkSize;

    if (!file_exists($filename))
    {
      $deferred->reject(new \RuntimeException("File not found: $filename"));
      return $deferred->promise();
    }

    // Команда для безопасного вывода файла в stdout
  //$cmd = [PHP_BINARY, '-r', 'echo file_get_contents($argv[1]);', '--', $filename, $chunkSize];
    $cmd = [PHP_BINARY, '-f', __DIR__.'/FileReaderProc.php', '--', $filename, $chunkSize];

    $descriptors = [
      1 => ["pipe", "w"], // stdout дочернего процесса
      2 => ["pipe", "w"]  // stderr
    ];

    // Открываем процесс
    $this->process = proc_open($cmd, $descriptors, $this->pipes);

    if(is_resource($this->process))
    {
      // Делаем потоки неблокирующими
      stream_set_blocking($this->pipes[1], false);
      stream_set_blocking($this->pipes[2], false);

      Loop::addReadStream($this->pipes[1], fn($stream)=>$this->OnRead(1, $stream));
      Loop::addReadStream($this->pipes[2], fn($stream)=>$this->OnRead(2, $stream));
    }
  }
  
  var $Received=[[],[]];
  
  function OnRead($Idx, $stream, $Finish=false)
  {
    global $Loader;
    $Stat=FStat($stream);
    $Meta=stream_get_meta_data($stream);
    $Loader->Log('Debug', 'OnRead ',$Idx,' Stat.Size=', $Stat['size'], ' Meta.unread_bytes=', $Meta['unread_bytes'])->Debug($Stat);
  //Echo '{Debug} OnRead Stat.Size=', $Stat['size'], "\n";
    $chunkSize=8192;
    $Len=0;
    while(true) //TODO: Windows
    {
      $chunk = fread($stream, $chunkSize);
      if(!Is_String($chunk)) break;
      $Loader->Log('Debug', '  chunk: ', StrLen($chunk));
      if($chunk==='') break;
      $Len+=StrLen($chunk);
      $this->Received[$Idx][]=$chunk;
      if(StrLen($chunk)<$chunkSize) break;
      break;
    }
      
  //If($Len)
    $Loader->Log('Debug', 'OnRead ', $Idx, ' Len=', $Len, ' Chunk', !Is_String($chunk)||!StrLen($chunk)? [$chunk]:StrLen($chunk));
    
    //Echo '{Debug} OnRead ', $Idx, ' ', $Len, "\n";
     
    //If(Is_String($chunk)) Return;
    
    //If($chunk !== false && !feof($stream)) Return; //$chunk === '' || $chunk === false)
     if($chunk===false) $Loader->Log('Debug', 'OnRead ', $Idx, ' Chunk=false');
     if(feof($stream)) $Loader->Log('Debug', 'OnRead ', $Idx, ' Eof');
     if($chunk==='') $Loader->Log('Debug', 'OnRead ', $Idx, ' Empty');
     if($Finish) return;
     $this->CheckFinish();
  }
  
  function CheckFinish()
  {
    // Проверяем, не завершился ли процесс
    $status = proc_get_status($this->process);
    if($status['running']) return;
    Loop::removeReadStream($this->pipes[1]);
    Loop::removeReadStream($this->pipes[2]);
    $this->OnRead(1, $this->pipes[1], true);
    $this->OnRead(2, $this->pipes[2], true);
    fclose($this->pipes[1]);
    fclose($this->pipes[2]);
    $this->pipes=[0,0];
    $Loader->Log('Debug', 'Exec Finish');
    $data  = Implode($this->Received[1]);
    $error = Implode($this->Received[2]);
    $this->Received=[];
    proc_close($this->process);
    $this->process = null;
    if(StrLen($error))
      $deferred->resolve($data); // Успешно отдаем ответ
    else
      $deferred->resolve($data); // Успешно отдаем ответ
  }

  // Проверяем готовность данных (вызывать в основном Event Loop)
  function tick(): bool 
  {
    if (!is_resource($this->process)) return false;

    $read = [$this->pipes[1]];
    $write = null;
    $except = null;

    // Проверяем, появились ли данные в пайпе без блокировки (таймаут 0)
    if(stream_select($read, $write, $except, 0) > 0)
    {
      $data = stream_get_contents($this->pipes[1]);
      $error = stream_get_contents($this->pipes[2]);

      // Закрываем всё
      fclose($this->pipes[1]);
      fclose($this->pipes[2]);
      proc_close($this->process);
      $this->process = null;

      // Вызываем колбэк
      ($this->callback)($error ? null : $data, $error ?: null);
      return false; // Чтение завершено
    }

    return true; // Процесс еще читает файл
  }
}