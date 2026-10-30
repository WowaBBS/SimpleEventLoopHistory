<?
class Stream_IO_Writer extends Stream_IO_Base
{
  private bool $isWriting = false;

  /**
   * Поставить задачу на запись в стек.
   * @param callable $writeFunc Функция записи: fn(Pipeline_Stream_Writer $w): bool
   * @param callable|null $callback Колбэк завершения: fn(?\Throwable $error)
   */
  public function write(callable $writeFunc, ?callable $callback = null): void
  {
    if(!$this->stack->add($readFunc, $callback)) return;

    if (!$this->isWriting)
      $this->execute();
  }

  /**
   * Попытка записи сырых байт в транспорт.
   * Возвращает false, если буфер ОС переполнен.
   */
  public function sendRaw(string $data): bool
  {
    // Наш SocketConnection::write() внутри себя использует Fiber::suspend(),
    // но здесь, согласно вашей спецификации, мы можем обрабатывать статус готовности асинхронно.
    return $this->stream->send($data);
  }

  public function raiseError(\Throwable $error): void
  {
    $current = array_pop($this->stack);
    if ($current && $current['callback'])
      ($current['callback'])($error);
    
    $this->execute();
  }

  private function execute(): void
  {
    if (empty($this->stack))
    {
      $this->isWriting = false;
      return;
    }

    $this->isWriting = true;
    $idx = array_key_last($this->stack);
    $current = &$this->stack[$idx];

    // Если вложенные функции записи ещё выполняются - ждем их
    if (isset($current['has_nested']) && !$current['nested_done']) return;

    // Вызываем функцию записи
    $isFinished = ($current['func'])($this);

    // Проверяем, наплодила ли функция вложенные подзадачи записи
    $newIdx = array_key_last($this->stack);
    if ($newIdx > $idx)
    {
      $current['has_nested'] = true;
      $current['nested_done'] = false;

      if (!$isFinished)
      {
        // Функция вернула false и передала вложенную функцию:
        // Она вызовется ЕЩЕ РАЗ, когда вложенная функция завершится (по спецификации)
        $current['recall_after_nested'] = true;
      }

      $this->execute();
      return;
    }

    if ($isFinished) {
      $doneJob = array_pop($this->stack);

      if ($doneJob['callback'])
        ($doneJob['callback'])(null);

      // Уведомляем родительскую функцию записи на стеке
      if (!empty($this->stack))
      {
        $parentIdx = array_key_last($this->stack);
        $parent = &$this->stack[$parentIdx];
        if (isset($parent['has_nested']))
        {
          $parent['nested_done'] = true;

          // Если родитель возвращал false, вызываем его ЕЩЕ РАЗ
          if (isset($parent['recall_after_nested']))
          {
            unset($parent['recall_after_nested']);
            $parent['has_nested'] = false; // Сбрасываем триггер ожидания
          }
        }
      }

      $this->execute();
    }
    else
    {
      // Если функция вернула false и не передавала вложенных задач -
      // значит сокет Беркли не готов к записи. Подписываемся на Writable событие в Loop
      Loop::addWriteStream($this->stream->getResource(), function() {
        Loop::removeWriteStream($this->transport->getResource());
        $this->execute(); // Перезапускаем шаг записи, как только сеть освободится
      });
    }
  }

//****************************************************************
// Stack

  protected function stream_resume () { $this->stream->resumeWrite (); }
  protected function stream_pause  () { $this->stream->pauseWrite  (); }
  
//****************************************************************
// Writer any data

  function writeData(string $data): int
  {
  }
  
  function writeList($args): int
  {
    $res = 0;
    foreach ($args as $arg)
      switch (gettype($arg))
      {
      case 'integer':
      case 'double':
        Logger::Warning('Writting numeric is not good')->backtrace();
        $arg = @(string)$arg;
      case 'string':
        $res += $this->writeData($arg);
        break;
      case 'array':
        if (array_is_list($arg))
        {
          $res += $this->writeList($arg);
          break;
        }
        throw new \LogicException('Unallowed write assoc array');
      case 'object': //TODO:
        if ($arg instanceof Stringable)
        {
          $res += $this->writeData($arg);
          break;
        }
      case 'boolean':
      default:
        throw new \LogicException('Unallowed write type '. gettype($arg));
      }
  }
  
//****************************************************************

}