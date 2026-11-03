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

  function waitData (                               ): bool   { return $this->reader->waitData (           ); }
  function waitMore (                               ): bool   { return $this->reader->waitMore (           ); }
  function readData (int    $size  ,?int $min=null  ): string { return $this->reader->readData ($args ,$min); }
  function peek     (int    $min=1 ,?int $max=null  ): string { return $this->reader->peek     ($min  ,$max); }
  function unread   (string $data  ,bool $new=false ): void   {        $this->reader->unread   ($data ,$new); }
  function skip     (int    $size                   ): void   {        $this->reader->skip     ($size      ); }
  function read     (...$args                  ):array|string { return $this->reader->readList ($args      ); }
  function until    (                               )         { return $this->reader->until    (           ); }
  function readLine (string $till="\n", bool $strict=false)   { return $this->reader->readLine ($till ,strict: $strict); }

//****************************************************************
// Life time
  function close() {} //TODO:
}