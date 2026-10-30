<?
class Stream_Buffer_String implements IStream_Buffer
{
  private string $data='';

  function get      (int $bytes):string { return substr($this->data, 0, $bytes); }
  function restSize ():int { return strlen($this->data); }
  function add      (string $data):void { $this->data .= $data; }
  function skip     (int $bytes):void { $this->data = substr($this->data, $bytes); }
  function readAll  ():string { return $this->read($this->restSize()); }
  
  function read (int $bytes):string
  {
    $result = $this->get($bytes);
    $this->skip($bytes);
    return $result;
  }
  
  function until (callable $find):false|string
  {
    $pos = $find($this->data, 0);
    if ($pos === false) return false;
    return $this->read($pos);
  }
}
