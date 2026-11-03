<?
class Stream_IO_Writer extends Stream_IO_Base
{
//****************************************************************
// Stream

  protected function stream_resume () { $this->stream->resumeSend ($this->onSend(...)); }
  protected function stream_pause  () { $this->stream->pauseSend  (); }
  
//****************************************************************
// Writer any data

  function asyncWrite(string $data, int $result=0): Promise
  {
    $func = function($writer) use(&$data, &$result){
      $len = $this->stream->send($data);
      if ($len === strlen($data)) return $result;
      if (!$len) return;
      $result += $len;
      $data = substr($data, $len);
    };

    $promise = $this->asyncTask($func);
    return $promise;
  }

  /**
   * Попытка записи сырых байт в транспорт.
   * Возвращает false, если буфер ОС переполнен.
   */
  function writeData(string $data): int
  {
    $result = 0;
    if ($len = $this->stream->send($data))
    {
      if ($len === strlen($data)) return $len;
      
      $data = substr($data, $len);
      $result += $len;
    }
    
    $promise = $this->asyncWrite($data, result: $result);
    return $promise->await();
  }
  
  function writeEnd()
  {
    $this->stream->sendEnd();
  }
  
  function onSend($stream)
  {
    $this->execute();
  }
  
  function writeList($args): int
  {
    $res = 0;
    foreach ($args as $arg)
      switch (gettype($arg))
      {
      case 'integer':
      case 'double':
        Logger::warning('Writting numeric is not good')->backtrace();
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