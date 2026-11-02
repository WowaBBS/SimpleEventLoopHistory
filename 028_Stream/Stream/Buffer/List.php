<?
class Stream_Buffer_List extends Stream_Buffer_Base
{
  private array $list   =[];
  private int   $offset =0;
  private int   $size   =0;
  
  const maxCountSkip =     128; // Valuable
  const maxCountAdd  = 16*1024; // Less effect
  const chunkSize    =       0; // Has negative effect

  function init        (string $data):void
  {
    $len = strlen($data);
    $this->list = $len? [$data]:[];
    $this->offset = 0;
    $this->size = $len;
  }

  function has         (            ):bool   { return $this->size > 0; }
  function peekPart    (int   $bytes):string { return substr(array_first($this->list)?? '', $this->offset, $bytes); }
  
  function restSize    (            ):int    { return $this->size; }

  function skip        (int   $bytes):void   
  {
    if (!$this->list) return;
    if (count($this->list) > self::maxCountSkip)
      $this->join();
    
    $this->size -= $bytes;
    if ($this->size < 0)
    {
      $bytes += $this->size;
      $this->size = 0;
    }
    
    $len = strlen($this->list[0]) - $this->offset;
    if ($len > $bytes)
    {
      $this->offset += $bytes;
      return;
    }
    
    $this->offset = 0;
    array_shift($this->list);
    $bytes -= $len;
    
    while ($bytes && $this->list)
    {
      $len = strlen($this->list[0]);
      if ($len > $bytes)
      {
        $this->offset = $bytes;
        return;
      }
      array_shift($this->list);
      $bytes -= $len;
    }
  }
  
  function dataOffs    (            ):array  { return [$this->join(), $this->offset]; }
  
  function join()
  {
    $list = $this->list;
    if (count($list) <= 1) return $list[0]?? '';
    $this->list = []; //Not to copy on write
    if ($offset = $this->offset)
      $list[0] = substr($list[0], $offset);
    $result = join($list);
    $this->init($result);
    return $result;
  }

  function add         (string $data):void
  {
    $len = strlen($data);
    $count = count($this->list);
    
    $this->size  += $len;
  //if ($count && strlen($this->list[$count-1])+$len < self::chunkSize)
  //  $this->list[$count-1] .= $data;
  //else
    $this->list[] = $data;
      
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
    
    array_unshift($this->list, $data);
  }
  
  static function hasFastUnreadNew    (): bool { return true; }
}
