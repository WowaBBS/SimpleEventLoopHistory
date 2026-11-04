<?php
interface IStream_Codec
{
  /** Чтение поля из стрима */
  function read(IStream $stream, array $context): mixed;
  
  /** Запись поля в стрим. Возвращает количество записанных байт */
  function write(IStream $stream, mixed $value, array $context): int;
}

/**
 * Базовый абстрактный класс для всех типов полей схемы
 */
abstract class Stream_Schema_Node implements IStream_Codec
{
  // Сюда можно вынести общие хелперы, например, resolveSign / prepareSign
}

/**
 * 
 */
class Stream_Schema_Custom implements IStream_Codec
{
  protected $read  ;
  protected $write ;

  function __construct($read, $write)
  {
    $this->read  = $read  ;
    $this->write = $write ;
  }
  
  function read  (IStream $stream,               array $context): mixed { return ($this->read  )($stream,         $context); }
  function write (IStream $stream, mixed $value, array $context): int   { return ($this->write )($stream, $value, $context); }
}
