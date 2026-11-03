<?
interface IStream_Stack
{
  function has(): bool;
  function isEmpty(): bool;
  function add(callable $handler, ?Deferred $callback = null):void;
  function call(...$args);
}
