<?
function sleepAsync(float $seconds): Promise
{
  $deferred = new Deferred();
  Loop::delay($seconds, fn() => $deferred->resolve());
  return $deferred->promise();
}
