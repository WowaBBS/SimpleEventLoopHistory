<?php
class Stream_Schema_Int_Utf8 extends Stream_Schema_Int
{ //TODO Use \WLib\CharSet\C_Utf8
  const rules = [
  //[format, and mask    , 2 or mask                          ,null array 3      ], #N bits      1b range   ensure
    ['C1', "\x7f"        , "\x00"                             ,[0]               ], #0 0xxxxxxx  0x00-0x7F  v&0x80==0x00
    [''  , ''            , ''                                 ,[]                ], #1 10xxxxxx  0x80-0xBF  v&0xC0==0x80
    ['C2', "\x1F?"       , "\xC0\x80"                         ,[0,0]             ], #2 110xxxxx  0xC0-0xDF  v&0xE0==0xC0
    ['C3', "\x0F??"      , "\xE0\x80\x80"                     ,[0,0,0]           ], #3 1110xxxx  0xE0-0xEF  v&0xF0==0xE0
    ['C4', "\x07???"     , "\xF0\x80\x80\x80"                 ,[0,0,0,0]         ], #4 11110xxx  0xF0-0xF7  v&0xF8==0xF0
    ['C5', "\x03????"    , "\xF8\x80\x80\x80\x80"             ,[0,0,0,0,0]       ], #5 111110xx  0xF8-0xFB  v&0xFC==0xF8
    ['C6', "\x01?????"   , "\xFC\x80\x80\x80\x80\x80"         ,[0,0,0,0,0,0]     ], #6 1111110x  0xFC-0xFD  v&0xFE==0xFC
    ['C7', "\x00??????"  , "\xFE\x80\x80\x80\x80\x80\x80"     ,[0,0,0,0,0,0,0]   ], #7 11111110  0xFE-0xFE  v&0xFF==0xFE
    ['C8', "\x00???????" , "\xFF\x80\x80\x80\x80\x80\x80\x80" ,[0,0,0,0,0,0,0,0] ], #8 11111111  0xFF-0xFF  v&0xFF==0xFF
  ];
  const high = [0,0,0,0,0,0,0,0 , 1,1,1,1,2,2,3,9];
  const low  = [4,4,4,4,4,4,4,4 , 5,5,5,5,6,6,7,8];
  
  const write = [ // fromat  or mask                             and mask
    0x0000000080 => 0,
    0x0000000800 => 2,
    0x0000010000 => 3,
    0x0000200000 => 4,
    0x0004000000 => 5,
    0x0080000000 => 6,
    0x1000000000 => 7,
    0x2000000000 => 8,
  ];

  public function read(IStream $stream, mixed &$value, $context): void
  {
    $firstByte = ord($stream->peek(1));
    $page = $firstByte<0xF0? self::high[$firstByte >> 4]:self::low[$firstByte & 15];
    [$format, $and] = self::rules[$page];
  //Logger::debug(['first='=>dechex($firstByte),'format'=>$format, 'and'=>bin2hex($and)]);
    if ($format === '')
      throw new \InvalidArgumentException('Utf8 codepoint '.$firstByte.' is out of range');
    $bytes = $stream->readData(strlen($and));
    $bytes &= $and;
    $data = unpack($format, $bytes);
    
    $value = 0;
    foreach($data as $v)
      $value = ($value << 6) + $v;
  }

  public function write(IStream $stream, mixed $value, $context): int
  {
    foreach (self::write as $limit=>$page)
    {
      if ($value >= $limit) continue;
      $rule = self::rules[$page];
      $bytes = $rule[3];
      for ($i = count($bytes)-1; $i >=0 ; $i--, $value >>= 6)
        $bytes[$i] = $value;
      
      $result  = pack($rule[0], ...$bytes);
      $result &=      $rule[1];
      $result |=      $rule[2];
      return $stream->writeData($result);
    }
    throw new \InvalidArgumentException('Utf8 codepoint '.$value.' is out of range');
  }
}
