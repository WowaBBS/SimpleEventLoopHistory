<?
abstract class Stream_Buffer_Base implements IStream_Buffer
{
  function __construct (string $data='', ?int $capacity=null)
  {
    $capacity ??= $this->capacity; $this->capacity = 0;
    $this->setCapacity($capacity);
    $this->init($data);
  }

  protected int $capacity =1024*1024; // 1 MB
  
  function getCapacity(): int { return $this->capacity; }
  function setCapacity(int $size): void { $this->capacity = $size; }

  final function clear    (): void { $this->init(''); }
  
  function getFree  (): int { return $this->getCapacity() - $this->restSize (); }
  
  function readAll():string { return $this->read($this->restSize()); }
  
  function read (int $bytes): string
  {
    $result = '';
    while ($bytes > 0 && $this->has())
    {
      $chunk = $this->recv($bytes);
    //Logger::debug(['chunk'=>$chunk, 'buff'=>$this]);
      $result .= $chunk;
      $bytes -= strlen($chunk);
    }
    return $result;
  }
  
  function recv (int $bytes): string
  {
    $result = $this->peekPart($bytes);
    $this->skip(strlen($result));
    return $result;
  }
  
  function peek (int $bytes): string
  {
    $result = $this->peekPart($bytes);
    $len = strlen($result);
    if ($len === $bytes) return $result;
    if ($len === $this->restSize()) return $result;
    
    $result = $this->read($bytes);
    $this->unread($result);
    return $result;
  }
  
  function until (callable $find, ?bool $include=null): ?string
  {
  /* Debug
    [$data, $offset] = $this->dataOffs();
    $pos = $find($data, $offset);
    Logger::debug('Until ', [$data, $offset], ' = ', $pos);
  */
    $pos = $find(...$this->dataOffs());
    if ($pos === null) return null;
    [$pos, $end] = $pos;
    if ($include === true) $pos += strlen($end);
    $result = $this->read($pos);
    if ($include === null) $this->skip(strlen($end));
    return $result;
  }

  static function hasFastUnreadNew   (): bool { return false; }
  static function hasFastUnreadExist (): bool { return static::hasFastUnreadNew(); }
}
