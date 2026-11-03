<?
class Stream_IO_Reader extends Stream_IO_Base
{
  function __construct(IStream_Async $stream)
  {
    parent::__construct($stream);
    $this->buffer = Stream_Buffer_Factory::create(type:'Reader');
  }

  function leakDispose()
  {
    parent::leakDispose();
    $this->buffer = Stream_Buffer_Null::Get();
  }
  
  function onRecv($stream): void
  {
    $chunk = $this->stream->recv($this->chunkSize);
  //Logger::debug('onRecv(', $this->chunkSize, ')=',[$chunk]);
    if ($chunk==='') //End of stream
    {
      if (!$this->stream->isRecvEnd())
      {
        Logger::error('onRecv: no data')->backtrace();
        return;
      }
      $this->onEnd();
    }
    else
    {
      $this->buffer->add($chunk);
      if ($this->buffer->restSize() < $this->required)
        return;
    }
    $this->execute();
  }
  
  function onEnd() { $this->isStreamEnd = true; }

  /** Уведомить стрим, сколько байт из буфера было успешно прочитано (потреблено) */
  function consume(int $bytes): void
  {
    $this->buffer->skip($bytes);
  //$this->stream->resumeRecv();
  }

//****************************************************************
// Stream

  protected function stream_resume () { $this->stream->resumeRecv ($this->onRecv(...)); }
  protected function stream_pause  () { $this->stream->pauseRecv  (); }
  
//****************************************************************
// Requirements

  private IStream_Buffer $buffer;
  private int $required  = 0;
  private int $expected  = 0;
  private int $chunkSize = 8*1024;

  /** Изменить требование по минимальному количеству байт для текущей функции */
  function require(int $bytes, ?int $expect=null)
  {
    $this->required = $bytes;
    $this->expected = max($bytes, $expect?? $this->chunkSize);
  }
  
  /** Изменить требование по минимальному количеству байт для текущей функции */
  function needMore(int $bytes=1, ?int $expect=null): void
  {
    $this->require($bytes+$this->buffer->restSize(), $expect);
  }
  
  function expect(int $bytes)
  {
    $this->expected = max($this->required, $bytes);
  }

  function expectMore(int $bytes)
  {
    $this->expected = max($this->required+$this->buffer->restSize(), $bytes);
  }

//****************************************************************
// sync

  private bool $isStreamEnd = false;
  private function isStreamEnd():bool { return $this->isStreamEnd; }
  public  function isEnd():bool { $this->readUntilMore(); return $this->isStreamEnd() && !$this->buffer->has(); }

  function asyncRead(int $size): Promise
  {
    $this->require($size, $size);
    
    $func = function($reader) use($size) {
      if ($reader->buffer->restSize() >= $size)
        return $reader->buffer->read($size);
      Logger::error('Expected size is ', $size, ', but actual is ', $reader->buffer)->throw();
    };
    
    $promise = $this->asyncTask($func);
    return $promise;
  }
  
  function readData(int $size): string
  {
    if ($this->buffer->restSize() >= $size)
      return $this->buffer->read($size);
    
    $promise = $this->asyncRead($size);
    return $promise->await();
  }

  static function untilMore($data, $offset) { return strlen($data)>$offset? [0, '']:false; }
  static function untilEnd($data, $offset) { return false; }
  
  static function untilStr($end)
  {
    return fn($data, $offset)=>($res = strpos($data, $end, $offset)) !== false? [$res-$offset, $end]:false;
  }
  
  static function untilReg($pattern)
  {
    return fn($data, $offset)=>preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, $offset)? [$matches[0][1]-$offset, $matches[0][0]]:false;
  }
  
  function readUntil(callable $until, ?bool $include=null, ?int $chunkSize=null, bool $orEnd=true)
  {
    $chunkSize ??= $this->chunkSize;
    $func = function($reader) use($until, $include, $chunkSize, $orEnd) {
      $result = $reader->buffer->until($until, $include);
      if ($result !== false)
        return $result;
      
      if (!$reader->isStreamEnd())
      {
        $this->require(1, $chunkSize);
        return false;
      }
      
      if ($orEnd)
        return $reader->buffer->readAll();
      
      throw new Stream_Exception_End();
    };

    $result = $func($this);
    if ($result !== false)
      return $result;
    
    $promise = $this->asyncTask($func);
    
    return $promise->await();
  }
  
  function readUntilMore(                                  ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntil(self::untilMore(...),  null   , $chunkSize, $orEnd); }
  function readUntilEnd (                                  ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntil(self::untilEnd (...),  null   , $chunkSize, $orEnd); }
  function readUntilStr(string $str  ,?bool $include=null, ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntil(self::untilStr($str), $include, $chunkSize, $orEnd); }
  function readUntilReg(string $reg  ,?bool $include=null, ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntil(self::untilReg($reg), $include, $chunkSize, $orEnd); }
  function readLine(string $end="\n" ,?bool $include=null, ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntilStr(            $end , $include, $chunkSize, $orEnd); }
  
  function readList($args): array|string
  {
    $res = [];
    foreach ($args as $arg)
      switch (gettype($arg))
      {
      case 'double':
        Logger::warning('Reading float size is not a good sign')->backtrace();
        $arg = @(int)$arg;
      case 'integer':
        $res[] = $this->readData($arg);
        break;
      case 'string':
      //TODO: Reader by name
        throw new \LogicException('Reader '.$arg.' is not supported');
        break;
      case 'array':
        if (array_is_list($arg))
        {
          $res[] = $this->readList($arg);
          break;
        }
        throw new \LogicException('Unallowed read assoc array');
      case 'object': //TODO:
      case 'boolean':
      default:
        throw new \LogicException('Unallowed write type '. gettype($arg));
      }
    if (count($res) === 1)
      return array_first($res);
    return $res;
  }
  
//****************************************************************
}
