<?
class Stream_Buffer_Null implements IStream_Buffer
{
  static function get() { static $result = new Static(); return $result; }

  #[\Override] function getLimit (             ): int    { return 0     ; }
  #[\Override] function setLimit (int    $size ): void   {                }
  #[\Override] function getFree  (             ): int    { return 0     ; }
  #[\Override] function has      (             ): bool   { return false ; }
  #[\Override] function peek     (int    $bytes): string { return ''    ; }
  #[\Override] function restSize (             ): int    { return 0     ; }
  #[\Override] function add      (string $data ): void   {                }
  #[\Override] function skip     (int    $bytes): void   {                }
  #[\Override] function read     (int    $bytes): string { return ''    ; }
  #[\Override] function readAll  (             ): string { return ''    ; }
  #[\Override] function unread   (string $data ): void   {                }
  #[\Override] function dataOffs (             ): array  { return ['', 0]; }
  #[\Override] function until    (callable $find, ?bool $include=null):false|string { return false; }
}
