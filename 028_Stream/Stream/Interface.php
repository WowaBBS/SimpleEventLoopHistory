<?
interface IStream
{
//****************************************************************
// Write

  function write     (...$args    ): int  ; // Записывает рекурсивно данные в writeData
  function writeData (string $data): int  ; // Записывает данные
  function writeEnd  (            ): void ; // Отправляет конец данных

//****************************************************************
// Read

  function waitData (                              ): bool   ; // Если данных на чтение нет, ждёт пока придут новые данные, false -- если пришёл конец данных
  function waitMore (                              ): bool   ; // Ждёт пока придут новые данные, false -- если пришёл конец данных
  function readData (int    $size  ,?int $min=null ): string ; // Читает size данных, если конец, то возвращает хотя бы $min данных, иначе эсепшн неожиданный конец данных.
  function peek     (int    $min=1 ,?int $max=null ): string ; // Предпросматривает от $min до $max, если данных недостаточно, ждёт их. Если слишком много обрезает.
  function unread   (string $data  ,bool $new=false): void   ; // Возвращает обратно прочитанные ранее данные, что бы прочитать ещё раз. Если $new, то подсовываются не те данные, которые были ранее прочитаны.
  function skip     (int    $size                  ): void   ; // Скипает $size байт
  function readLine (string $till="\n", bool $strict=false)  ; // Читает строку до $till и сам $till скипает, если не $strict и данные кончились, возвращает их.
  function read     (...$args                 ):array|string ; 
  function until    (                              )         ;

//****************************************************************
// Others

  function schema(IStream_Codec $schema): Stream_Schema_Context;

  function close();
}
