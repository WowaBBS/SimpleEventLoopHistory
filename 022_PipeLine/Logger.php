<?
/**
 * Мок-Логгер, который пишет сообщения в консоль асинхронно
 */
class Logger
{
  static function logAsync(string $message): Promise
  {
    $deferred = new Deferred();

    // Имитируем небольшую задержку записи на диск (например, 1 мс)
    Loop::delay(0.001, function() use ($deferred, $message) {
      echo "[" . date('H:i:s') . "] " . $message . "\n";
      $deferred->resolve(null);
    });

    return $deferred->promise();
  }
}
