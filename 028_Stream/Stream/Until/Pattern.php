<?
class Stream_Until_Pattern extends Stream_Until_Base
{
  public string $pattern='';
  
  function __construct(
     string $pattern   ,
    ?bool   $include   =null,
    ?int    $chunkSize =null,
     bool   $orEnd     =true,
    ?int    $limit     =null,
  )
  {
    $this->pattern = $pattern;
    parent::__construct(
      $include   ,
      $chunkSize ,
      $orEnd     ,
      $limit     ,
    );
  }
  
  function onUntil($data, $offset): false|array
  {
    if (preg_match($this->pattern, $data, $matches, PREG_OFFSET_CAPTURE, $offset))
      return [$matches[0][1]-$offset, $matches[0][0]];
      
    return false;
  }
}
