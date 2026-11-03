<?
interface IStream_Async
{
//****************************************************************
// Read

  function recv       (int      $maxSize  ): ?string; // Чтение данных из потока
  function isRecvEnd  (                   ): bool; // Проверка, закрыт ли поток для чтения
  function resumeRecv (callable $callback ): void; // Подписаться на событие при успешном чтении
  function pauseRecv  (                   ): void;  // Отписаться от события

//function hasDataToRecv (): bool ; // Проверка, есть ли данные для чтения
//function restRecvSize  (): int  ; // Сколько осталось? если у внутренний буфера

//****************************************************************
// Send

  function send       (string   $data    ): false|int; // Запись данных в поток
  function sendEnd    (                  ): void; // Закрыть поток для записи
  function resumeSend (callable $callback): void; // Подписаться на событие при успешной записи
  function pauseSend  (                  ): void; // Отписаться от события

//function canSendSize (): int;
//function canSend     (): bool; // Проверка, свободен ли поток для записи

//****************************************************************
// Time life
  function close();
}
