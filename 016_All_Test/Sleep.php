<?
function sleepAsync(float $seconds): Promise
{
  $deferred = new Deferred();
  Loop::delay($seconds, fn() => $deferred->resolve(null));
  return $deferred->promise();
}
