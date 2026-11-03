<?
class Stream_Until_Delimiter extends Stream_Until_Base
{
  public string $delimiter = '';
  
  function __construct(
     string $delimiter ,
    ?bool   $include   =null,
    ?int    $chunkSize =null,
     bool   $orEnd     =true,
    ?int    $limit     =null,
  )
  {
    $this->delimiter = $delimiter;
    parent::__construct(
      $include   ,
      $chunkSize ,
      $orEnd     ,
      $limit     ,
    );
  }
  
  function onUntil($data, $offset): false|array
  {
    $res = strpos($data, $this->delimiter, $offset);
    
    if ($res === false) return false;
    
    return [$res-$offset, $this->delimiter];
  }
}
