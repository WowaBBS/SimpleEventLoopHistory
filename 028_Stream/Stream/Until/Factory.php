<?
class Stream_Until_Factory
{
  protected $reader;

  function __construct($reader) { $this->reader = $reader; }

  function more(          ) { return new Stream_Until_More      ($this->reader      ); }
  function end (          ) { return new Stream_Until_End       ($this->reader      ); }
  function str(string $str) { return new Stream_Until_Delimiter ($this->reader, $str); }
  function reg(string $reg) { return new Stream_Until_Pattern   ($this->reader, $reg); }
}
