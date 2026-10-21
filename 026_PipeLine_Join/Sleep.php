<?
function sleepAsync(float $seconds): Promise
{
  $deferred = new Deferred();
  Loop::delay($seconds, $deferred->resolve(...));
  return $deferred->promise();
}

function lazySleep(float $timeout): Promise 
{ 
  return Promise::lazyDeferred(
    fn($deferred, $timeout)=>Loop::delay($timeout, $deferred->resolve(...)),
    $timeout
  ); 
}
