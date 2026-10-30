<?
interface IStream_Buffer
{
  function get      (int $bytes):string;
  function restSize ():int;
  function add      (string $data):void;
  function skip     (int $bytes):void;
  function read     (int $bytes):string;
  function until    (callable $find):false|string;
}