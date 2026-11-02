<?
interface IStream_Buffer
{
  function getCapacity (            ): int    ; // Получает ограничение на размер хранимых данных
  function setCapacity (int    $size): void   ; // Ограничивает размер хранимых данных
  function clear       (            ): void   ; // Очищает буффер
  function init        (string $data): void   ; // Инциализирует данными
  function getFree     (            ): int    ; // Сколько свободного места доступно для записи
  function has         (            ): bool   ; // Проверяет есть ли данные
  function peek        (int   $bytes): string ; // Получает следующие $bytes данные, но данные не удаляются
  function peekPart    (int   $bytes): string ; // Получает следующие $bytes данные, но данные не удаляются
  function restSize    (            ): int    ; // Размер оставшихся в буффере данных
  function add         (string $data): void   ; // Добавляет данные в конец буффера
  function skip        (int   $bytes): void   ; // Пропускает $bytes байт из буффера
  function read        (int   $bytes): string ; // Читает $bytes байт из буффера
  function recv        (int   $bytes): string ; // Читает $bytes байт из буффера, может вернуть меньше, чем есть из-за особенностей буфера
  function readAll     (            ): string ; // Читает оставшиеся данные из буффера
  function unread      (string $data): void   ; // Добавляет данные в начало буффера
  function dataOffs    (            ): array  ; // Получает массив из двух элементов: строка буффера и омещение начала данных в ней
  
  // Ищет данные в буффере и возвращает их,
  //    функции $find передается строка буффера и смещение в нём, 
  //       она возвращает false,
  //       если данные не нейдены -- массив из двух элементов: позиция строки окончания и сама строка окончания
  //    $include
  //       false -- результат не включает конец, он остаётся в буффере.
  //       null  -- результат не включает конец, но он скпинут
  //       true  -- результат включает конец
  function until    (callable $find, ?bool $include=null): false|string;
  
  static function hasFastUnreadNew   (): bool; //List
  static function hasFastUnreadExist (): bool; //Offset, List
}