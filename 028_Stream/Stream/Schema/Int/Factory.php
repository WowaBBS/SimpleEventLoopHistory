<?php
if (!defined('isLittleEndian')) define('isLittleEndian', unpack('S',"\x01\x00")[1] === 1);

class Stream_Schema_Int_Factory
{
  static function get(string $name): IStream_Codec { return self::try($name)?? throw new \LogicException("Неизвестный бинарный формат: '$name'"); }
  static function try(string $name): ?IStream_Codec
  {
    return match ($name) {
    #  // Go like
    #  // Default       // Little endian       // Big endian          // Machine endian 
    # 'int8'  ,'uint8'  ,
    # 'int16' ,'uint16' ,'int16le' ,'uint16le' ,'int16be' ,'uint16be' ,'int16me' ,'uint16me' ,
    # 'int24' ,'uint24' ,'int24le' ,'uint24le' ,'int24be' ,'uint24be' ,'int24me' ,'uint24me' ,
    # 'int32' ,'uint32' ,'int32le' ,'uint32le' ,'int32be' ,'uint32be' ,'int32me' ,'uint32me' ,
    # 'int48' ,'uint48' ,'int48le' ,'uint48le' ,'int48be' ,'uint48be' ,'int48me' ,'uint48me' ,
    # 'int64' ,'uint64' ,'int64le' ,'uint64le' ,'int64be' ,'uint64be' ,'int64me' ,'uint64me' ,
    # 'float32', 'float32le', 'float32be', 'float32me',
    # 'float64', 'float64le', 'float64be', 'float64me' =>self::get(strtr($name, ['uint'=>'UInt', 'int'=>'Int', 'float'=>'Float'])),
      
      'int16'  ,'int24'  ,'int32'  ,'int48'  ,'int64'  ,'float32' ,
      'uint16' ,'uint24' ,'uint32' ,'uint48' ,'uint64' ,'float64' =>self::manualEndian($name),

      'int16me'  ,'int24me'  ,'int32me'  ,'int48me'  ,'int64me'  ,'float32me' ,
      'uint16me' ,'uint24me' ,'uint32me' ,'uint48me' ,'uint64me' ,'float64me' =>self::machineEndian($name),
      
      // Rusl like
      // Default    i   le   u        i   be   u        i   me   u
      'i8'  ,'u8'  ,
      'i16' ,'u16' ,'i16le' ,'u16le' ,'i16be' ,'u16be' ,'i16me' ,'u16me' ,
      'i24' ,'u24' ,'i24le' ,'u24le' ,'i24be' ,'u24be' ,'i24me' ,'u24me' ,
      'i32' ,'u32' ,'i32le' ,'u32le' ,'i32be' ,'u32be' ,'i32me' ,'u32me' ,
      'i48' ,'u48' ,'i48le' ,'u48le' ,'i48be' ,'u48be' ,'i48me' ,'u48me' ,
      'i64' ,'u64' ,'i64le' ,'u64le' ,'i64be' ,'u64be' ,'i64me' ,'u64me' ,
      'f32' ,'f32le' ,'f32be', 'f32me', 'f64' ,'f64le' ,'f64be', 'f64me',
        =>self::get(strtr($name, ['u'=>'uint', 'i'=>'int', 'f'=>'float'])),
        
      // Нативные быстрые типы на базе BasePack
      'uint8'     => self::pack('C', 1),   'int8'      => self::pack('c', 1),
      'uint16be'  => self::pack('n', 2),   'uint16le'  => self::pack('v', 2),
      'uint32be'  => self::pack('N', 4),   'uint32le'  => self::pack('V', 4),
      'int64be'   => self::pack('J', 8),   'int64le'   => self::pack('P', 8),
      'float32be' => self::pack('G', 4),   'float32le' => self::pack('g', 4),
      'float64be' => self::pack('E', 8),   'float64le' => self::pack('e', 8),

      // Знаковые типы с коррекцией на базе наследования от беззнаковых BasePack
      'int16be', 'int24be', 'int32be', 'int48be',
      'int16le', 'int24le', 'int32le', 'int48le' => self::signed('u'.$name),
          
      'uint64le', 'uint64be' => self::get('i'.substr($name, 1)),
      
      // Полностью изолированные кастомные классы
      'uint24be' => self::pack2('Cn', 3 ,16, 0),
      'uint24le' => self::pack2('vC', 3 , 0,16),

      'uint48be' => self::pack2('nN', 6 ,32, 0),
      'uint48le' => self::pack2('Vv', 6 , 0,32),

      'int/wtf8'    => new Stream_Schema_Int_Utf8(),
      'int/utf8'    => new Stream_Schema_Int_Utf8(),
    /* TODO: in the nearest future
      'int/utf16le' => new Stream_Schema_Int_Utf16(),
      'int/utf16be' => new Stream_Schema_Int_Utf16(),
      'int/msb'     => new Stream_Schema_Int_Msb(),
      'zigzag'      => new Stream_Schema_Int_ZigZag(),
    */
      default => null
    };
  }
  
  static function isLittleEndian() { static $res = unpack('S',"\x01\x00")[1] === 1; return $res; }
  
  static string $defaultEndian = 'le'; // be, me
  
  protected static function manualEndian($name)
  {
    //TODO: make to oveerride for structs
    return self::get($name.(self::$defaultEndian));
  }
  
  protected function machineEndian($name)
  {
    return self::get(substr($name, 0, -2).(self::isLittleEndian()? 'le':'be'));
  }
  
  protected static function pack(string $mask, int $size)
  {
    return new Stream_Schema_Int_Pack($mask, $size);
  }
  
  protected static function pack2(string $mask, int $size, int $offset1, int $offset2)
  {
    return new Stream_Schema_Int_Pack2($mask, $size, $offset1, $offset2);
  }

  protected static function signed(string $unsignedName)
  {
    $unsigned = self::get($unsignedName);
    $size = $unsigned->getSize();
    if ($size < 0) throw new LogicException($unsignedName.' has no size');
    $limit = 1 << (($size<<3) -1);
  //Logger::debug($unsignedName, ' signed limit ',$limit,', size: ',$size);
    return new Stream_Schema_Int_Signed($unsigned, $limit);
  }
}
