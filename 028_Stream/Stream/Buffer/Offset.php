<?
class Stream_Buffer_Offset extends Stream_Buffer_String
{
  private int $offset=0;

  function clear       (            ):void   { parent::clear(); $this->offset=0; }
  function has         (            ):bool   { return strlen($this->data)>0; }
  function peek        (int   $bytes):string { return substr($this->data, $this->offset, $bytes); }
  function restSize    (            ):int    { return strlen($this->data) - $this->offset; }
  function add         (string $data):void   { $this->data .= $data; }
  function skip        (int   $bytes):void   { $this->offset += $bytes; if ($this->restSize()<=0) $this->clear(); }
  function dataOffs    (            ):array  { return [$this->data, $this->offset]; }
  function unread      (string $data):void  
  {
    if ($this->offset < strlen($data))
    {
      $this->data = $data.substr($this->data, $this->offset);
      $this->offset = 0;
    }
    else
      $this->offset -= strlen($data);
  }
}
