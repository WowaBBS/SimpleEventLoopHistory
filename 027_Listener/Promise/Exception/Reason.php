<?
/**
 * Исключение, выбрасываемое при отмене операции
 */
class ReasonException extends AbortException
{
  function getReason() { return $this->getMessage(); }
}