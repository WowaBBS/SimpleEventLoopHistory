<?
class Stream_Async_Null implements IStream_Async, IDebug_Leak_Object
{
  function leakDispose() {}
  
  static function get() { static $result = new Static(); return $result; }
  
//#[\Override] function hasDataToRecv(): bool { return false; }
//#[\Override] function restRecvSize(): int { return 0; }
  #[\Override] function recv(int $maxSize): ?string { return null; }
  #[\Override] function isRecvEnd(): bool { return true; }
  #[\Override] function resumeRecv(callable $callback): void {} //TODO: throw
  #[\Override] function pauseRecv(): void {}
  
//#[\Override] function canSendSize(): int { return 0; }
//#[\Override] function canSend(): bool { return false; }
  #[\Override] function send(string $data): false|int { return false; }
  #[\Override] function sendEnd(): void {}
  #[\Override] function resumeSend(callable $callback): void {} //TODO: twrow
  #[\Override] function pauseSend(): void {}
  #[\Override] function close() {}
}