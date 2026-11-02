<?
interface IStream_Async
{
//****************************************************************
// Read

  // Проверка, есть ли данные для чтения
  function hasDataToRecv(): bool;
  // Сколько осталось? если у внутренний буфера
  function restRecvSize(): int;
  // Чтение данных из потока
  function recv(int $maxSize): ?string;
  // Проверка, закрыт ли поток для чтения
  function isRecvEnd(): bool;
  // Подписаться на событие при успешном чтении
  function resumeRecv(callable $callback): void;
  // Отписаться от события
  function pauseRecv(): void;

//****************************************************************
// Send

  function canSendSize(): int;
  function canSend(): bool;
  function send(string $data): false|int;

  // Закрыть поток для записи
  function sendEnd(): void;
  function resumeSend(callable $callback): void;
  function pauseSend(): void;

//****************************************************************
// Time life
  function close();
/*
  // Закрыть поток для чтения
  public function closeRecv(): void;

  // Запись данных в поток
  public function write(string $data): void;

  // Проверка, свободен ли поток для записи
  public function isWriteFree(): bool;

  // Подписаться на событие при успешной записи
  public function onWrite(callable $callback): void;

  // Отписаться от события
  public function offWrite(callable $callback): void;  
*/
}
