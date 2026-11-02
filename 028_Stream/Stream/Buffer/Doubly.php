<?
class Stream_Buffer_Doubly extends Stream_Buffer_Base
{
  private SplDoublyLinkedList  $list;
  private int   $offset =0;
  private int   $size   =0;
  
  const maxCountSkip =     128; // Valuable
  const maxCountAdd  = 16*1024; // Less effect
  const chunkSize    =       0; // Has negative effect

  function init        (string $data):void
  {
    $len = strlen($data);
    $this->list = new SplDoublyLinkedList();
    if ($len)
      $this->list->push($data);
    $this->offset = 0;
    $this->size = $len;
  }
  
  function list_first(): string { return $this->list->isEmpty()? '':$this->list->bottom(); }

  function has         (            ):bool   { return $this->size > 0; }
  function peekPart    (int   $bytes):string { return substr($this->list_first(), $this->offset, $bytes); }
  
  function restSize    (            ):int    { return $this->size; }

  function skip        (int   $bytes):void   
  {
    if ($this->list->isEmpty()) return;
    if ($this->list->count() > self::maxCountSkip)
      $this->join();
    
    $this->size -= $bytes;
    if ($this->size < 0) // Trim bytes
    {
      $bytes += $this->size;
      $this->size = 0;
    }
    
    $len = strlen($this->list->bottom()) - $this->offset;
    if ($len > $bytes)
    {
      $this->offset += $bytes;
      return;
    }
    
    $bytes -= $len;
    $this->offset = 0;
    $this->list->shift();

    while ($bytes && !$this->list->isEmpty())
    {
      $len = strlen($this->bottom());
      if ($len > $bytes)
      {
        $this->offset = $bytes;
        return;
      }
      $this->list->shift();
      $bytes -= $len;
    }
  }
  
  function dataOffs    (            ):array  { return [$this->join(), $this->offset]; }
  
  function join()
  {
    $count = $this->list->count();
    if ($count <= 1) return $count? $this->list->bottom():'';
    
    $list = iterator_to_array($this->list);
    
    while (!$this->list->isEmpty())
      $this->list->shift();
    
    if ($offset = $this->offset)
      $list[0] = substr($list[0], $offset);
    $result = join($list);
    $this->init($result);
    return $result;
  }

  function add         (string $data):void
  {
    $len = strlen($data);
    $count = $this->list->count();
    
    $this->size  += $len;
    
  //if ($count && strlen($this->list->top())+$len < self::chunkSize)
  //  $data = $this->list->pop().$data;

    $this->list->push($data);
      
    if ($count+1 >= self::maxCountAdd)
      $this->join();
  }
  
  function unread      (string $data):void  
  {
    $len = strlen($data);
    $this->size += $len;
    if ($offset = $this->offset)
    {
      if ($offset >= $len)
      {
        $this->offset -= $len;
        return;
      }
      
      $this->offset = 0;
      $data = substr($data, 0, -$offset);
      $len -= $offset;
    }
    
    $this->list->unshift($data);
  }
  
  static function hasFastUnreadNew    (): bool { return true; }
}
