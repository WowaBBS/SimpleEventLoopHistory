<?php
interface IStream_Codec
{
  function getSize(): int;
  
  /** Чтение поля из стрима */
  function read(IStream $stream, mixed &$value, $context): void;
  
  /** Запись поля в стрим. Возвращает количество записанных байт */
  function write(IStream $stream, mixed $value, $context): int;
}

/**
 * Базовый абстрактный класс для всех типов полей схемы
 */
abstract class Stream_Schema_Node implements IStream_Codec
{
  // Сюда можно вынести общие хелперы, например, resolveSign / prepareSign
  function getSize(): int { return -1; }
}

/**
 * 
 */
class Stream_Schema_Custom extends Stream_Schema_Node
{
  protected $read  ;
  protected $write ;

  function __construct($read, $write)
  {
    $this->read  = $read  ;
    $this->write = $write ;
  }
  
  function read  (IStream $stream, mixed &$value ,$context): void  { $value=($this->read  )($stream         ,$context); }
  function write (IStream $stream, mixed  $value ,$context): int   { return ($this->write )($stream ,$value ,$context); }
}
