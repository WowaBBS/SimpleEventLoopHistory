<?
class Stream_Buffer_String extends Stream_Buffer_Base
{
  protected string $data='';
  
  function __construct (string $data='')  { $this->data = $data; }
  function clear    (            ):void   { $this->data = ''; }
  function has      (            ):bool   { return strlen($this->data)>0; }
  function peek     (int   $bytes):string { return substr($this->data, 0, $bytes); }
  function restSize (            ):int    { return strlen($this->data); }
  function add      (string $data):void   { $this->data .= $data; }
  function skip     (int   $bytes):void   { $this->data = substr($this->data, $bytes); }
  function dataOffs (            ):array  { return [$this->data, 0]; }
  function unread   (string $data):void   { $this->data = $data.$this->data; }
}
