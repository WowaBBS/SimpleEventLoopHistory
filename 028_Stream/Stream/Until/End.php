<?
class Stream_Until_End extends Stream_Until_Base
{
  function __construct(
    ?int    $chunkSize =null,
     bool   $orEnd     =true,
    ?int    $limit     =null,
  )
  {
    parent::__construct(
      include   :  null      ,
      chunkSize : $chunkSize ,
      orEnd     : $orEnd     ,
      limit     : $limit     ,
    );
  }
  
  function onUntil($data, $offset): false|array
  {
    return false;
  }
}
