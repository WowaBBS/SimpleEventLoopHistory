<?php
class Deferred
{
  private Promise $promise;

  function __Construct() { $this->promise = new Promise(); }

  function Promise(): Promise { return $this->promise; }

  function resolve(mixed $value=null): void { $this->promise->resolve($value); }
  function reject(mixed $reason): void { $this->promise->reject($reason); }
}
