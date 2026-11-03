<?
class Stream_Until_More extends Stream_Until_Base
{
  function __construct(
    ?int    $chunkSize =null,
     bool   $orEnd     =true,
  )
  {
    parent::__construct(
      include   :  null      ,
      chunkSize : $chunkSize ,
      orEnd     : $orEnd     ,
      limit     :  null      ,
    );
  }
  
  function onUntil($data, $offset): false|array
  {
    return strlen($data) > $offset? [0, '']:false;
  }
}
