<?
class FileReaderExec
{
  private $process;
  private $pipes = [];
  private $callback;

  function __construct(string $filePath, callable $callback)
  {
    $this->callback = $callback;

    // Команда для безопасного вывода файла в stdout
  //$cmd = sprintf('php -r "echo file_get_contents(%s);"', escapeshellarg($filePath));
  //$cmd = sprintf(PHP_BINARY.' -r "echo file_get_contents(%s);"', EscapeShellArg($filePath));
  //$cmd = [PHP_BINARY, '-r', 'echo file_get_contents("'.EscapeShellArg($filePath).'");'];
    $cmd = [PHP_BINARY, '-r', 'echo file_get_contents($argv[1]);', '--', $filePath];
    
    $descriptors = [
      1 => ["pipe", "w"], // stdout дочернего процесса
      2 => ["pipe", "w"]  // stderr
    ];

    // Открываем процесс
    $this->process = proc_open($cmd, $descriptors, $this->pipes);

    if(is_resource($this->process))
    {
      // Делаем потоки неблокирующими
      stream_set_blocking($this->pipes[1], false);
      stream_set_blocking($this->pipes[2], false);
    }
  }

  // Проверяем готовность данных (вызывать в основном Event Loop)
  function tick(): bool 
  {
    if (!is_resource($this->process)) return false;

    $read = [$this->pipes[1]];
    $write = null;
    $except = null;

    // Проверяем, появились ли данные в пайпе без блокировки (таймаут 0)
    if(stream_select($read, $write, $except, 0) > 0)
    {
      $data = stream_get_contents($this->pipes[1]);
      $error = stream_get_contents($this->pipes[2]);

      // Закрываем всё
      fclose($this->pipes[1]);
      fclose($this->pipes[2]);
      proc_close($this->process);
      $this->process = null;

      // Вызываем колбэк
      ($this->callback)($error ? null : $data, $error ?: null);
      return false; // Чтение завершено
    }

    return true; // Процесс еще читает файл
  }
}