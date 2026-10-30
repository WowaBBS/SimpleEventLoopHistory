<?
abstract class Stream_Stack_Base implements IStream_Stack
{
  static function isFinished($result) { return $result!==false && $result!==null; }
  
  function getHandler():callable { return null; }
  function getDeferred():?Deferred { return null; }

  function call(...$args)
  {
    try
    {
      $handler = $this->getHandler();
      $result = $handler(...$args);
      if (!self::isFinished($result)) return;
      
      $this->getDeferred()?->resolve($result);
    }
    catch(\Throwable $e)
    {
      $this->getDeferred()?->reject($e);
    }
  }
  
}
