<?
interface IStream_Buffer
{
// Получает ограничение на размер хранимых данных
  function getLimit (): int;
// Ограничивает размер хранимых данных
  function setLimit (int $size): void;
// Сколько свободного места доступно для записи
  function getFree  (): int;
// Проверяет есть ли данные
  function has      ():bool;
// Получает следующие $bytes данные, но данные не удаляются
  function peek     (int $bytes):string;
// Размер оставшихся в буффере данных
  function restSize ():int;
// Добавляет данные в конец буффера
  function add      (string $data):void;
// Пропускает $bytes байт из буффера
  function skip     (int $bytes):void;
// Читает $bytes байт из буффера
  function read     (int $bytes):string;
// Читает оставшиеся данные из буффера
  function readAll  ():string;
// Добавляет данные в начало буффера
  function unread   (string $data):void;
// Получает массив из двух элементов: строка буффера и омещение начала данных в ней
  function dataOffs ():array;
// Ищет данные в буффере и возвращает их, 
//    функции $find передается строка буффера и смещение в нём, 
//       она возвращает false,
//       если данные не нейдены -- массив из двух элементов: позиция строки окончания и сама строка окончания
//    $include
//       false -- результат не включает конец, он остаётся в буффере.
//       null  -- результат не включает конец, но он скпинут
//       true  -- результат включает конец
  function until    (callable $find, ?bool $include=null):false|string;
}