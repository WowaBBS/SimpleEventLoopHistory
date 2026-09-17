<?
/**
 * Òîêåí îòìåíû, ïåğåäàâàåìûé âíóòğü àñèíõğîííûõ ôóíêöèé
 */
class AbortSignal 
{
  private bool $aborted = false;
  private ?string $reason = null;
  private array $listeners = [];

  function isAborted(): bool { return $this->aborted; }
  function getReason(): ?string { return $this->reason; }

  /**
   * Ïîçâîëÿåò àñèíõğîííîé ôóíêöèè ïîäïèñàòüñÿ íà ñîáûòèå îòìåíû
   */
  function onAbort(callable $listener): void
  {
    if ($this->aborted) 
    {
      $listener($this->reason);
      return;
    }
    $this->listeners[] = $listener;
  }

  /**
   * Âíóòğåííèé ìåòîä, âûçûâàåìûé òîëüêî èç AbortController
   */
  function triggerAbort(?string $reason = null): void
  {
    if ($this->aborted) return;
    $this->aborted = true;
    $this->reason = $reason ?? "Îïåğàöèÿ áûëà îòìåíåíà.";

    foreach ($this->listeners as $listener)
      $listener($this->reason);
    
    $this->listeners = [];
  }
}