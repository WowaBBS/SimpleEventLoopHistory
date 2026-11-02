<?
abstract class Stream_Async_Base implements IStream_Async, IDebug_Leak_Object
{
  function syncStream() { return new Stream_Sync($this); }
  
  function __construct () { Debug_Leak_Tracker::register($this); }
  
  function leakDispose() {}
}