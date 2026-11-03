<?
class Stream_Buffer_Null implements IStream_Buffer
{
  static function get() { static $result = new Static(); return $result; }

  #[\Override] function getCapacity (             ): int    { return 0     ; }
  #[\Override] function setCapacity (int    $size ): void   {                }
  #[\Override] function clear       (             ): void   {                }
  #[\Override] function init        (string $data ): void   {                }
  #[\Override] function getFree     (             ): int    { return 0     ; }
  #[\Override] function has         (             ): bool   { return false ; }
  #[\Override] function peek        (int    $bytes): string { return ''    ; }
  #[\Override] function peekPart    (int    $bytes): string { return ''    ; }
  #[\Override] function restSize    (             ): int    { return 0     ; }
  #[\Override] function add         (string $data ): void   {                }
  #[\Override] function skip        (int    $bytes): void   {                }
  #[\Override] function recv        (int    $bytes): string { return ''    ; }
  #[\Override] function read        (int    $bytes): string { return ''    ; }
  #[\Override] function readAll     (             ): string { return ''    ; }
  #[\Override] function unread      (string $data ): void   {                }
  #[\Override] function dataOffs    (             ): array  { return ['', 0]; }
  #[\Override] function until       (callable $find, ?bool $include=null):?string { return null; }

  static function hasFastUnreadNew   (): bool { return false; }
  static function hasFastUnreadExist (): bool { return false; }
}
