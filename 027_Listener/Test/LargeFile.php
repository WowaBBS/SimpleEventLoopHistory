<?php

Function LargeFile(
  $fileName ='large_test_file.txt',
  $text     ="Здесь много текста для проверки асинхронности. ",
  $repeat   =50000,
)
{
  $dir=__DIR__.'/Data';
  @mkdir($dir);
  $fileName=$dir.'/'.$fileName;
  
  if (is_file($fileName)) return $fileName;
  
  file_put_contents($fileName, str_repeat($text, $repeat));
  return $fileName;
}
