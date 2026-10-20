<?
enum Pipeline_Event {
  case DATA  ;
  case ERROR ;
  case END   ;
  
  function isData  () { return $this===self::DATA  ; }
  function isError () { return $this===self::ERROR ; }
  function isEnd   () { return $this===self::END   ; }
}
