<?
class Stream_IO_Writer extends Stream_IO_Base
{
//****************************************************************
// Stack

  protected function stream_resume () { $this->stream->resumeSend ($this->onSend(...)); }
  protected function stream_pause  () { $this->stream->pauseSend  (); }
  
  /**
   * Поставить задачу на запись в стек.
   * @param callable $writeFunc Функция записи: fn(Pipeline_Stream_Writer $w): bool
   * @param callable|null $callback Колбэк завершения: fn(?\Throwable $error)
   */
  function asyncTask(callable $ioFunc):Promise
  {
    $deferred = new Deferred();
    $this->stack_add($ioFunc, $deferred);
    return $deferred->Promise();
  }
  
  /**
   * Поставить задачу на запись в стек.
   * @param callable $writeFunc Функция записи: fn(Pipeline_Stream_Writer $w): bool
   * @param callable|null $callback Колбэк завершения: fn(?\Throwable $error)
   */
  function addTask(callable $ioFunc): void
  {
    $this->stack_add($ioFunc);
  }  
  
  public function raiseError(\Throwable $error): void
  {
    $current = array_pop($this->stack);
    if ($current && $current['callback'])
      ($current['callback'])($error);
    
    $this->execute();
  }

  private function execute(): void
  {
    $this->stack_call($this);
  }
  
//****************************************************************
// Writer any data

  /**
   * Попытка записи сырых байт в транспорт.
   * Возвращает false, если буфер ОС переполнен.
   */
  function writeData(string $data): int // TODO:exception
  {
    $result = 0;
    $len = $this->stream->send($data);
    if ($len === strlen($data)) return $len;
    if ($len) $data = substr($data, $len);
    $result += $len;
    
    $func = function($writer) use(&$data, &$result){
      $len = $this->stream->send($data);
      $result += $len;
      if ($len === strlen($data)) return $result;
      if ($len) $data = substr($data, $len);
    //$this->asyncTask(Closure::getCurrent()); // Will recall current
    };

    $promise = $this->asyncTask($func);
    return $promise->await();
  //$promise->await();
  //return $result;
  }
  
  function writeEnd()
  {
    $this->stream->sendEnd();
  }
  
  function onSend($stream)
  {
  //Logger::debug('onSend()');
    $this->execute();
  /*
    $len = $this->stream->send($data);
    if ($len === strlen($data))
    {
      $this->stream->pauseSend();
      return;
    }
    
    $this->toSend = $len? substr($data, $len):$data;
  */
  }
  
  function writeList($args): int
  {
    $res = 0;
    foreach ($args as $arg)
      switch (gettype($arg))
      {
      case 'integer':
      case 'double':
        Logger::Warning('Writting numeric is not good')->backtrace();
        $arg = @(string)$arg;
      case 'string':
        $res += $this->writeData($arg);
        break;
      case 'array':
        if (array_is_list($arg))
        {
          $res += $this->writeList($arg);
          break;
        }
        throw new \LogicException('Unallowed write assoc array');
      case 'object': //TODO:
        if ($arg instanceof Stringable)
        {
          $res += $this->writeData($arg);
          break;
        }
      case 'boolean':
      default:
        throw new \LogicException('Unallowed write type '. gettype($arg));
      }
    return $res;
  }
  
//****************************************************************

}