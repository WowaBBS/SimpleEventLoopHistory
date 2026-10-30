<?
class Stream_IO_Reader extends Stream_IO_Base
{
  function __construct(IStream $stream)
  {
    parent::__construct($stream);
    $this->buffer = new Stream_Buffer_String();
  }

  function onData($stream): void
  {
    $chunk = $this->stream->recv($this->chunkSize);
    $this->buffer->add($chunk);
    if($this->buffer->restSize() < $this->required)
      return;
    $this->execute();
  }

  /** Уведомить стрим, сколько байт из буфера было успешно прочитано (потреблено) */
  function consume(int $bytes): void
  {
    $this->buffer->skip($bytes);
  //$this->stream->resumeRead();
  }

//****************************************************************
// Stack

  protected function stream_resume () { $this->stream->resumeRead (); }
  protected function stream_pause  () { $this->stream->pauseRead  (); }
  
  /**
   * Поставить задачу на чтение в стек.
   * @param callable $readFunc Функция чтения: fn(Pipeline_Stream_Reader $r, string $buf): bool
   * @param callable|null $callback Колбэк завершения: fn(?\Throwable $error)
   */
  function asyncTask(callable $ioFunc):Promise
  {
    $deferred = new Deferred();
    $this->stack_add($ioFunc, $deferred);
    return $deferred->Promise();
  }
  
  function addTask(callable $ioFunc): void
  {
    $this->stack_add($ioFunc);
  }

  /** Выбросить ошибку формата или сетевого сбоя прямо во время чтения */
  function raiseError(\Throwable $error): void
  {
    $current = array_pop($this->stack);
    if ($current && $current['callback'])
      ($current['callback'])($error);
    
    $this->execute(); // Продолжаем крутить стек для остальных задач, если они есть
  }

  private function execute(): void
  {
    $this->stack_call($this);
  }
  
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

  function readData(int $size):string
  {
    if ($this->buffer->restSize() >= $size)
      return $this->buffer->read($size);
    
    $this->require($size, $size);
    
    $readFunc = fn($reader)=>
      $reader->readData($size);
    
    $promise = $this->asyncTask($readFunc);
    
    return $promise->await();
  }

  
  static function untilStr($end, $include=false)
  {
    if(!$include) return fn($data, $offset)=>strpos($data, $end, $offset);
    return fn($data, $offset)=>($res=strpos($data, $end, $offset))!==false? $res+strlen($end):false;
  }
  
  static function untilReg($pattern, $include=false)
  {
    if(!$include) return fn($data, $offset)=>preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, $offset)? $matches[0][1]:false;
    return fn($data, $offset)=>preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, $offset)? $matches[0][1]+strlen($matches[0][0]):false;
  }
  
  function readUntil(callable $until, ?int $chunkSize=null, bool $orEnd=true)
  {
    $chunkSize ??= $this->chunkSize;
    $readFunc=function($reader) use($until, $chunkSize, $orEnd){
      $result = $reader->buffer->until($until);
      if ($result !== false)
        return $result;
      
      if (!$reader->isStreamFinished())
      {
        $this->require(1, $chunkSize);
        return false;
      }
      
      if ($orEnd)
        return $reader->readAll();
      
      throw new Stream_Exception_End();
    };

    $result = $readFunc($this);
    if ($result !== false)
      return $result;
    
    $promise = $this->asyncTask($readFunc);
    
    return $promise->await();
  }
  
  function readUntilStr(string $str  ,bool $include=false, ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntil(self::untilStr($str, $include), $chunkSize, $orEnd); }
  function readUntilReg(string $reg  ,bool $include=false, ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntil(self::untilReg($reg, $include), $chunkSize, $orEnd); }
  function readLine(string $end="\n" ,bool $include=false, ?int $chunkSize=null, bool $orEnd=true) { return $this->readUntilStr(            $end, $include , $chunkSize, $orEnd); }
  
  function readList($args):array|string
  {
    $res = [];
    foreach ($args as $arg)
      switch (gettype($arg))
      {
      case 'double':
        Logger::Warning('Reading float size is not good')->backtrace();
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
