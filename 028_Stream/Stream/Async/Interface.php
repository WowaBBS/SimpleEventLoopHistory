<?
interface IStream_Async
{
//****************************************************************
// Read

  // Проверка, есть ли данные для чтения
//function hasDataToRecv(): bool;
  // Сколько осталось? если у внутренний буфера
//function restRecvSize(): int;
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

//function canSendSize(): int;
  // Проверка, свободен ли поток для записи
//function canSend(): bool;
  // Запись данных в поток
  function send(string $data): false|int;

  // Закрыть поток для записи
  function sendEnd(): void;
  // Подписаться на событие при успешной записи
  function resumeSend(callable $callback): void;
  // Отписаться от события
  function pauseSend(): void;

//****************************************************************
// Time life
  function close();
}
