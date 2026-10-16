<?
/**
 * ”правл€ющий объект дл€ отмены задач
 */
class AbortController
{
  private AbortSignal $signal;

  function __construct() { $this->signal = new AbortSignal(); }
  function getSignal(): AbortSignal { return $this->signal; }
  function abort(?string $reason = null): void { $this->signal->triggerAbort($reason); }
}