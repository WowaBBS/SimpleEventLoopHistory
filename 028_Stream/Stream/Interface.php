<?
interface IStream
{
/*
//****************************************************************
// Read

  // Сколько осталось? если у внутренний буфер
  function restRecvSize(): bool;
  // Чтение данных из потока
  function recv(int $maxSize): ?string;
  // Проверка, есть ли данные для чтения
  function hasDataToRecv(): bool;
  
  function isRecvEnd(): bool;
//function isReading(): bool;
  function resumeRead(): void;
  function pauseRead(): void;

//****************************************************************
// Recv

//****************************************************************
// Write
  function waitWrite(): void;
  function send(string $data): false|int;

  // Закрыть поток для записи
  public function closeWrite(): void;
  function closeSending(): void;
*/
//****************************************************************
// Time life
  function close();
}
