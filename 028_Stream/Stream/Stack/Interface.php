<?
interface IStream_Stack
{
  function has(): bool;
  function isEmpty(): bool;
  function add(callable $handler, $callback = null):void;
  function call(...$args);
}
