<?
class Stream_Async_LoopBackSimple implements IStream_Async
{
  private string $buffer = '';
  private bool $isEnd = false;
  /** @var callable|null */
  private $recvCallback = null;
  
  function isValid(): bool { return true; }

  public function recv(int $maxSize): ?string
  {
    if ($this->buffer === '' && $this->isEnd) return null;
    $chunk = substr($this->buffer, 0, $maxSize);
    $this->buffer = substr($this->buffer, strlen($chunk));
    return $chunk;
  }

  public function isRecvEnd(): bool { return $this->isEnd; }

  public function resumeRecv(callable $callback): void
  {
    $this->recvCallback = $callback;
    // Если в буфере уже что-то есть, сразу пинаем таску в Loop
    if ($this->buffer !== '')
      Loop::delay(0, $this->triggerRecv(...));
  }

  public function pauseRecv(): void { $this->recvCallback = null; }

  public function send(string $data): false|int
  {
    if ($this->isEnd) return false;
    $this->buffer .= $data;
    // Если кто-то ждет данные на чтение, уведомляем его
    if ($this->recvCallback)
      Loop::delay(0, $this->triggerRecv(...));
    
    return strlen($data);
  }

  public function sendEnd(): void { $this->isEnd = true; }
  public function resumeSend(callable $callback): void { Loop::delay(0, $callback); }
  public function pauseSend(): void {}
  public function close(): void { $this->buffer = ''; $this->isEnd = true; }

  private function triggerRecv(): void
  {
    if (!$this->recvCallback) return;
    
    $cb = $this->recvCallback;
    $this->recvCallback = null; // атомарный сброс
    $cb();
  }
}