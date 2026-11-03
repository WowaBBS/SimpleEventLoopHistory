<?
class Stream_Sync implements IStream, IDebug_Leak_Object
{
  protected $reader { get=>$this->reader ??= new Stream_IO_Reader($this->stream); }
  protected $writer { get=>$this->writer ??= new Stream_IO_Writer($this->stream); }
  protected ?IStream_Async $stream;
  
  function __construct(IStream_Async $stream) { $this->stream = $stream; Debug_Leak_Tracker::register($this); }

  function leakDispose()
  {
    new ReflectionProperty(self::class, 'reader')->getRawValue($this)?->leakDispose();
    new ReflectionProperty(self::class, 'writer')->getRawValue($this)?->leakDispose();
    
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

  function isEnd():bool                { return $this->reader->isEnd   (     ); } //TODO: Sync
  function read(...$args):array|string { return $this->reader->readList($args); }
  function readData(int $size): string { return $this->reader->readData($args); }
  
  function until() { return $this->reader->until(); }
  function readLine(string $end="\n", ?bool $include=null) { return $this->reader->readLine($end, include:$include); }

//****************************************************************
// Life time
  function close() {}
}