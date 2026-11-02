<?
class Stream_Buffer_Offset extends Stream_Buffer_Base
{
  private string $data   ='';
  private int    $offset =0;

  function init     (string $data):void   { $this->data = $data; $this->offset=0; }
  function has      (            ):bool   { return strlen($this->data)>0; }
  function peek     (int   $bytes):string { return substr($this->data, $this->offset, $bytes); }
  function peekPart (int   $bytes):string { return $this->peek($bytes); }
  function restSize (            ):int    { return strlen($this->data) - $this->offset; }
  function skip     (int   $bytes):void   { $this->offset += $bytes; if ($this->restSize()<=0) $this->clear(); }
  function dataOffs (            ):array  { return [$this->data, $this->offset]; }

  function add      (string $data):void
  {
    if ($offset = $this->offset)
      $this->init(substr($this->data, $offset).$data);
    else
      $this->data .= $data;
  }
  
  function unread   (string $data):void  
  {
    if ($this->offset < strlen($data))
      $this->init($data.substr($this->data, $this->offset));
    else
      $this->offset -= strlen($data);
  }

  static function hasFastUnreadExist (): bool { return true; }
}
