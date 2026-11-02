<?
class Stream_Sync implements IStream, ILeakObject
{
  protected $reader { get=>$this->reader ??= new Stream_IO_Reader($this->stream); }
  protected $writer { get=>$this->writer ??= new Stream_IO_Writer($this->stream); }
  protected ?IStream_Async $stream;
  
  function __construct(IStream_Async $stream) { $this->stream = $stream; Loop_LeakTracker::register($this); }

  function leakDispose()
  {
    new ReflectionProperty(self::class, 'reader')->getRawValue($this)?->leakDispose();
    new ReflectionProperty(self::class, 'writer')->getRawValue($this)?->leakDispose();
    
  //$map = (array)$this;
  //$map['*reader']?->leakDispose();
  //$map['*writer']?->leakDispose();
    
    $this->reader = null;
    $this->writer = null;
    $this->stream = Stream_Async_Null::get();
  }
  
//****************************************************************
// Write

  function write     (...$args    ): int  { return $this->writer->writeList ($args); }
  function writeData (string $data): int  { return $this->writer->writeData ($args); }
  function writeEnd  (            ): void {        $this->writer->writeEnd  (     ); }

//****************************************************************
// Read

  function read(...$args):array|string { return $this->reader->readList($args); }
  function readData(int $size): string { return $this->reader->readData($args); }
  
  function readUntil    (callable $until  ,                     ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readUntil    ($until ,          $chunkSize, $orEnd); }
  function readUntilStr (string $str      ,?bool $include=null, ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readUntilStr ($str   ,$include, $chunkSize, $orEnd); }
  function readUntilReg (string $reg      ,?bool $include=null, ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readUntilReg ($reg   ,$include, $chunkSize, $orEnd); }
  function readLine     (string $end="\n" ,?bool $include=null, ?int $chunkSize=null, bool $orEnd=true) { return $this->reader->readLine     ($end   ,$include, $chunkSize, $orEnd); }

//function read($len) {}
//function write($len) {}
//****************************************************************
// Life time
  function close() {}
}