<?php
/**
 * Исключение, выбрасываемое при отмене операции
 */
class TimeoutException extends AbortException
{
  protected float $timeout=0;

  function __construct(string $message = '', float $timeout=0)
  {
    $this->timeout = $timeout;
    parent::__construct($message);
  }
  
  function getTimeout() { return $this->timeout; }
  
  static function start(float $timeout, ?string $reason=null)
  {
    // Создаем промис-таймер, который гарантированно упадет через указанное время.
    // Для создания таймера используем наш Loop::delay.
    $deferred = new Deferred();
    
    Loop::delay($timeout, function() use ($deferred, $reason, $timeout) {
      $reason ??= "The operation exceeded the timeout limit of {$timeout} seconds.";
      $deferred->reject(new TimeoutException($reason, $timeout));
    });

    // Запускаем гонку между текущим промисом ($this) и промисом-таймером.
    // Возвращаем результат этой гонки.
    return $deferred->promise();
  }
}