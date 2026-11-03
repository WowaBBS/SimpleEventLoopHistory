<?
class Stream_Until_Delimiter extends Stream_Until_Base
{
  public string $delimiter = '';
  
  function __construct($reader, string $delimiter)
  {
    parent::__construct($reader);
    $this->delimiter = $delimiter;
  }
  
  function onUntil($data, $offset): false|array
  {
    $res = strpos($data, $this->delimiter, $offset);
    
    if ($res === false) return false;
    
    return [$res - $offset, $this->delimiter];
  }
}
