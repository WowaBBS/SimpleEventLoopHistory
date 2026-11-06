<?php
class Assert
{
  static function equals($desired, $actual, $message=null)
  {
    if (self::isIdentical($desired, $actual))  return true;
    $log = Logger::error('Assert::equals, ', $message);
    $showType = gettype($desired) !== gettype($actual);
    self::showValue($log, 'desired ',$desired  ,$showType);
    self::showValue($log, 'actual  ',$actual   ,$showType);
    return false;
  }
  
  static function isIdentical($a, $b)
  {
    if ($a === $b) return true;
    $z=$a==$b;
    
    if (gettype($a) !== gettype($b)) return false;
    
    if (is_float($a)) return @(string)$a === @(string)$b;
    
    if (is_array($a))
    {
      if (count($a) !== count($b)) return false;
      $isList = array_is_list($a);
      if ($isList !== array_is_list($b)) return false;
      if ($isList)
      {
        for ($i = 0, $c = count($a); $i < $c; $i++)
          if (!self::isIdentical($a[$i], $b[$i]))
            return false;
        return true;
      }
      $keys = array_keys($a);
      if ($keys !== array_keys($b)) return false;
      
      foreach ($keys as $i)
        if (!self::isIdentical($a[$i], $b[$i]))
          return false;
      return true;
    }
    
    Return false;
  }
  
  static function showValue($log, $info ,$value ,$showType)
  {
    $type = gettype($value);
    if ($showType)
      $info.='('.$type.'):';
    else
      $info.=':';
    
    $inline = false;
    switch ($type)
    {
    case 'NULL'    :
    case 'boolean' :
    case 'integer' :
    case 'double'  :
      $inline = true;
      break;
    case 'string':
      $encoding = Mb_Detect_EnCoding($value, ['UTF-8', 'ISO-8859-1', 'ASCII'], true);
      if ($encoding === false)
      {
        $inline = strlen($value) <= 16;
        $value = \WLib\Debug\Format::HexDump($value);
      }
      else
        $inline = strpos("\n", $value) === false;
      break;
    default:
      return $log($info)->dump($value);
    }
    
    if ($inline)
      return $log($info, ' ', $value);
    return $log($info)($value);
  }
}