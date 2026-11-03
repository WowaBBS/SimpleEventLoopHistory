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
// Buffer

  private IStream_Buffer $buffer;
  
  function buffer_until    (callable $find, ?bool $include=null): false|string { return $this->buffer->until    ($find, $include); }
  function buffer_readAll  (                                   ):       string { return $this->buffer->readAll  (               ); }
  
//****************************************************************
// Requirements

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
  public  function isStreamEnd():bool { return $this->isStreamEnd; } //TODO: private
  public  function isEnd():bool { $this->until()->more()->read(); return $this->isStreamEnd() && !$this->buffer->has(); }

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

  function until(): Stream_Until_Factory { return new Stream_Until_Factory($this); }

  function readLine(string $end="\n", ?bool $include=null) { return $this->until()->str($end)->include($include)->read(); }

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
