<?
class Deferred
{
  private Promise $promise;

  function __Construct() { $this->promise = new Promise(); }

  function Promise(): Promise { return $this->promise; }

  function Resolve(mixed $value): void { $this->promise->resolve($value); }
  function Reject(mixed $reason): void { $this->promise->reject($reason); }
}
