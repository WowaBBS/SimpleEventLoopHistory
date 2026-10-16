<?
class Logger
{
  /**
   * Мок-Логгер, который пишет сообщения в консоль асинхронно
   */
  static function logAsync(...$args): Promise
  {
    $deferred = new Deferred();

    // Имитируем небольшую задержку записи на диск (например, 1 мс)
    Loop::delay(0.001, function() use ($deferred, $args) {
      self::log(...$args);
      $deferred->resolve(null);
    });

    return $deferred->promise();
  }
  
  static function get() { global $Loader; return $Loader; }
  static protected function push($level, ...$args) { return self::get()->Log($level, ...$args); }
  
  static function exception(\Throwable $e, ...$args) { return self::error(...$args)->Exception($e); }
  static function error (...$args) { return self::push('Error' , ...$args); }
  static function debug (...$args) { return self::push('Debug' , ...$args); }
  static function log   (...$args) { return self::push('Log'   , ...$args); }
}
