<?
abstract class Stream_Stack_Base implements IStream_Stack, ILeakObject
{
  static function isFinished($result) { return $result!==false && $result!==null; }
  
  function leakDispose() {}
  
  function getHandler():callable { return null; }
  function getDeferred():?Deferred { return null; }

  function call(...$args)
  {
    try
    {
      $handler = $this->getHandler();
      $result = $handler(...$args);
      if (!self::isFinished($result)) return;
      $handler = null; //Free
      $this->getDeferred()?->resolve($result);
    }
    catch(\Throwable $e)
    {
      Logger::Exception($e, static::class, ': call handler exception');
      $this->getDeferred()?->reject($e);
    }
  }
  
}
