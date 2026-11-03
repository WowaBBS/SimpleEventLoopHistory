<?
class Stream_Buffer_Factory
{
  public static array  $list = [
    'String' =>Stream_Buffer_String ::class,
    'Offset' =>Stream_Buffer_Offset ::class,
    'List'   =>Stream_Buffer_List   ::class,
    'Doubly' =>Stream_Buffer_Doubly ::class,
  //'Ring'   =>Stream_Buffer_Ring   ::class,
  ];
  public static array  $defaults = [
    'Default'  =>Stream_Buffer_List  ::class,
    'LoopBack' =>Stream_Buffer_List  ::class,
    'Reader'   =>Stream_Buffer_List  ::class,
  # 'Reader'   =>Stream_Buffer_List  ::class,
    'Datagram' =>Stream_Buffer_List  ::class,
  ];
  
  static function create(string $data='', ?int $capacity=null, string $type='Default'): IStream_Buffer
  {
    $class = self::$defaults[$type]
      ?? $list[$type]
      ?? Logger::error('Unknown Stream Buffer Type: ', $type)
        ->ret(self::$defaults['Default']);
    return new $class(
      data     : $data     ,
      capacity : $capacity ,
    );
  }
}
