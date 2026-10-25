<?
/**
 * Асинхронно и неблокирующе записывает строку в сокет.
 * Если буфер полон, файбер засыпает и ждет готовности через IoWatcher.
 */
function writeAsync($socket, string $data, int $clientId): void
{
  $length = strlen($data);
  $written = 0;
  $wasPaused = false;

  while ($written < $length)
  {
    // Пытаемся записать оставшийся кусок данных
    $chunk = substr($data, $written);
    $result = @fwrite($socket, $chunk);

    if ($result === false)
      throw new \RuntimeException("Error writing to client socket #$clientId");

    if ($result > 0)
    {
      $written += $result;
      if ($wasPaused)
      {
        Logger::logAsync("[Client #$clientId] The network buffer has become available. Sending has resumed.");
        $wasPaused = false;
      }
      continue;
    }

    // Если записалось 0 байт - сетевой буфер ОС переполнен!
    if (!$wasPaused)
    {
      Logger::logAsync("[Client #$clientId] Network buffer full! Sending temporarily paused...");
      $wasPaused = true;
    }

    // Ставим текущий файбер на паузу, пока IoWatcher не скажет, что сокет готов к записи
    $currentFiber = \Fiber::getCurrent();
    if ($currentFiber === null)
      throw new \LogicException('writeAsync can only be called from within a fiber.');

    Loop::addWriteStream($socket, function($stream) use ($currentFiber, $socket) {
      // Как только сокет готов принимать байты - убираем его из отслеживания записи и будим файбер
      Loop::removeWriteStream($socket);
      if (!$currentFiber->isTerminated() && $currentFiber->isSuspended())
        $currentFiber->resume();
    });

    \Fiber::suspend(); // Засыпаем
  }
}