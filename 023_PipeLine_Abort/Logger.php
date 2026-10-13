<?
/**
 * Мок-Логгер, который пишет сообщения в консоль асинхронно
 */
class Logger
{
  static function logAsync(string ...$args): Promise
  {
    $deferred = new Deferred();

    // Имитируем небольшую задержку записи на диск (например, 1 мс)
    Loop::delay(0.001, function() use ($deferred, $args) {
      global $Loader;
      $Loader->Log('Log', ...$args);
      $deferred->resolve(null);
    });

    return $deferred->promise();
  }
}
