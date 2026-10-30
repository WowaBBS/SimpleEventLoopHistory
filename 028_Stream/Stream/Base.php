<?
abstract class Stream_Base implements IStream
{
  var $reader { get=>$this->reader ??= new Stream_IO_Reader($this); }
  var $writer { get=>$this->writer ??= new Stream_IO_Writer($this); }
  
//****************************************************************
// Write

  function write     (...$args    ): int { return $this->writer->writeList($args); }
  function writeData (string $data): int { return $this->writer->writeData($args); }

//****************************************************************
// Read

  function read(...$args):array|string { return $this->reader->readList($args); }
  function readData(int $size): string { return $this->reader->readData($args); }
  
  function readUntil    (callable $until  ,                     ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readUntil    ($until ,          $chunkSize, $orEnd); }
  function readUntilStr (string $str      ,bool $include=false, ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readUntilStr ($str   ,$include, $chunkSize, $orEnd); }
  function readUntilReg (string $reg      ,bool $include=false, ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readUntilReg ($reg   ,$include, $chunkSize, $orEnd); }
  function readLine     (string $end="\n" ,bool $include=false, ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readLine     ($end   ,$include, $chunkSize, $orEnd); }

//function read($len) {}
//function write($len) {}
}