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
    $chunk = $stream->recv($this->chunkSize);
  //Logger::debug('onRecv(', $this->chunkSize, ')=',[$chunk]);
    if ($chunk==='' || $chunk===false) //End of stream
    {
      if (!$stream->isRecvEnd())
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
  
  function onEnd() { $this->isRecvEnd = true; }

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
  
  function buffer_until    (callable $find, ?bool $include=null):?string { return $this->buffer->until    ($find, $include); }
  function buffer_readAll  (                                   ): string { return $this->buffer->readAll  (               ); }
  
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

  private bool $isRecvEnd = false;
  public  function isRecvEnd():bool { return $this->isRecvEnd; } //TODO: private
  
  protected function isEnd():bool { return $this->isRecvEnd() && !$this->buffer->has(); }
  
  public function waitData():bool { $this->until()->more()->read(); return !$this->isEnd(); }
  public function waitMore():bool { $this->until()->more($this->buffer->restSize())->read(); return !$this->isEnd(); }

  function asyncRead(int $size, ?int $min=null): Promise
  {
    $this->require($size, $size);
    
    $func = function($reader) use($size, $min) {
      if ($reader->buffer->restSize() >= $size)
        return $reader->buffer->read($size);
      if ($min !== null && $this->buffer->restSize() >= $min)
        return $this->buffer_readAll();
    
      if ($this->isRecvEnd())
        throw new Stream_Exception_End();
      
      // timeout?
      Logger::error('Expected size is ', $size, ', but actual is ', $reader->buffer)->throw();
    };
    
    $promise = $this->asyncTask($func);
    return $promise;
  }
  
  function readData(int $size, ?int $min=null): string
  {
    if ($this->buffer->restSize() >= $size)
      return $this->buffer->read($size);
    if ($this->isRecvEnd()) //Or timeout?
      if ($min !== null && $this->buffer->restSize() >= $min)
        return $this->buffer_readAll();
      else
        throw new Stream_Exception_End();
    
    $promise = $this->asyncRead($size, $min);
    return $promise->await();
  }
  
  function peek(int $min=1, ?int $max=null): string
  { //TODO: Optimize
    $result = $this->readData($min);
    $this->unread($result);
    return $result;
  }
  
  function unread(string $data, bool $new=false): void
  {
    $this->buffer->unread($data, $new); //TODO: new;
  }

  function skip(int $size): void
  { //TODO: Optimize
    while ($size > 0)
      $size -= strlen($this->read(min($this->chunksize, $size)));
  }
  
  function until(): Stream_Until_Factory { return new Stream_Until_Factory($this); }

  function readLine(string $end="\n", bool $strict=false) { return $this->until()->str($end)->strict($strict)->read(); }

  function read(...$args): array|string
  {
    if (empty($args)) return $this->readData(1, 0);
    if (is_float($args[0]))
    {
      Logger::warning('Reading float size is not a good sign')->backtrace();
      $arg[0] = @(int)$args[0];
    }
    if (is_int($args[0])) return $this->readData((int)$args[0], $args[1] ?? null); //Check count 2

    $arg = count($args) === 1 ? $args[0] : $args;
    if (is_callable ($arg)) return $arg($this);
    // РЕШЕНИЕ: Если пришел массив-карта, отдаем его в хелпер, передавая сам стрим
    if (is_array    ($arg)) return $this->schema(Stream_Schema_Factory::create($arg));
    if ($arg instanceof IStream_Codec) return $this->schema($arg)->read();
    
    throw new \InvalidArgumentException('Unallowed read type '. gettype($arg));
  }
  
//****************************************************************
// Unused

  function write     (    ...$args): int  { return $this->unknown('write'     ,$args); }
  function writeData (string $data): int  { return $this->unknown('writeData' ,$data); }
  function writeEnd  (            ): void {        $this->unknown('writeEnd'        ); }

  function close() {} //TODO:
  
//****************************************************************
}
