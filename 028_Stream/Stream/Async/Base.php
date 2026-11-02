<?
abstract class Stream_Async_Base implements IStream_Async, ILeakObject
{
  function syncStream() { return new Stream_Sync($this); }
  
  function __construct () { Loop_LeakTracker::register($this); }
  
  function leakDispose() {}
}