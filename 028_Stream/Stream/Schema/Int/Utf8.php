<?php
class Stream_Schema_Int_Utf8 extends Stream_Schema_Int
{ //TODO Use \WLib\CharSet\C_Utf8
  const read = [ //[format, and mask]
    ['C1', "\x7f"                             ], #0 0xxxxxxx  0x00-0x7F  v&0x80==0x00
    [''  , ''                                 ], #1 10xxxxxx  0x80-0xBF  v&0xC0==0x80
    ['C2', "\x1f\x3f"                         ], #2 110xxxxx  0xC0-0xDF  v&0xE0==0xC0
    ['C3', "\x0f\x3f\x3f"                     ], #3 1110xxxx  0xE0-0xEF  v&0xF0==0xE0
    ['C4', "\x07\x3f\x3f\x3f"                 ], #4 11110xxx  0xF0-0xF7  v&0xF8==0xF0
    ['C5', "\x03\x3f\x3f\x3f\x3f"             ], #5 111110xx  0xF8-0xFB  v&0xFC==0xF8
    ['C6', "\x01\x3f\x3f\x3f\x3f\x3f"         ], #6 1111110x  0xFC-0xFD  v&0xFE==0xFC
    ['C7', "\x00\x3f\x3f\x3f\x3f\x3f\x3f"     ], #7 11111110  0xFE-0xFE  v&0xFF==0xFE
    ['C8', "\x00\x3f\x3f\x3f\x3f\x3f\x3f\x3f" ], #8 11111111  0xFF-0xFF  v&0xFF==0xFF
  ];
  const high = [0,0,0,0,0,0,0,0 , 1,1,1,1,2,2,3,9];
  const low  = [4,4,4,4,4,4,4,4 , 5,5,5,5,6,6,7,8];
  
  const write = [ // fromat  or mask                             and mask
    0x0000000080 => ['C1', "\x00"                             ,"\x7F"        ,[0]],
    0x0000000800 => ['C2', "\xC0\x80"                         ,"\x1F?"       ,[0,0]],
    0x0000010000 => ['C3', "\xE0\x80\x80"                     ,"\x0F??"      ,[0,0,0]],
    0x0000200000 => ['C4', "\xF0\x80\x80\x80"                 ,"\x07???"     ,[0,0,0,0]],
    0x0004000000 => ['C5', "\xF8\x80\x80\x80\x80"             ,"\x03????"    ,[0,0,0,0,0]],
    0x0080000000 => ['C6', "\xFC\x80\x80\x80\x80\x80"         ,"\x01?????"   ,[0,0,0,0,0,0]],
    0x1000000000 => ['C7', "\xFE\x80\x80\x80\x80\x80\x80"     ,"\x00??????"  ,[0,0,0,0,0,0,0]],
    0x2000000000 => ['C8', "\xFF\x80\x80\x80\x80\x80\x80\x80" ,"\x00???????" ,[0,0,0,0,0,0,0,0]],
  ];

  public function read(IStream $stream, array $context): int
  {
    $firstByte = ord($stream->peek(1));
    $page = $firstByte<0xF0? self::high[$firstByte >> 4]:self::low[$firstByte & 15];
    [$format, $and] = self::read[$page];
  //Logger::debug(['first='=>dechex($firstByte),'format'=>$format, 'and'=>bin2hex($and)]);
    if ($format === '')
      throw new \InvalidArgumentException('Utf8 codepoint '.$firstByte.' is out of range');
    $bytes = $stream->readData(strlen($and));
    $bytes &= $and;
    $data = unpack($format, $bytes);
    
    $result = 0;
    foreach($data as $v)
      $result = ($result << 6) + $v;
    return $result;
  }

  public function write(IStream $stream, mixed $v, array $context): int
  {
    foreach (self::write as $limit=>$rule)
    {
      if ($v >= $limit) continue;
      $bytes = $rule[3]; //8-1
      for ($i = count($bytes)-1; $i >=0 ; $i--, $v >>= 6)
        $bytes[$i] = $v;
      
      $result  = pack($rule[0], ...$bytes);
      $result &=      $rule[2];
      $result |=      $rule[1];
      return $stream->writeData($result);
    }
    throw new \InvalidArgumentException('Utf8 codepoint '.$v.' is out of range');
  }
}
