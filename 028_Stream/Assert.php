<?php
class Assert
{
  static function equals($desired, $actual, $message=null)
  {
    if ($desired === $actual) return true;
    Logger::error('Assert::equals, ', $message)
      ('desired : ',$desired  )
      ('actual  : ',$actual   );
    return false;
  }
}