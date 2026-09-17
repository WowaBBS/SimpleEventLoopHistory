<?php

// 1. Указываем путь к файлу
$filePath  =         $argv[1];
$chunkSize =   (Int)($argv[2]?? 8192);
$sleep     = (Float)($argv[3]?? .1);
$sleep*=1000000;

// Проверяем, существует ли файл и доступен ли для чтения
if (!file_exists($filePath) || !is_readable($filePath)) 
{
  fwrite(STDERR, "Error: File not found or is not readable.\n");
  exit(1);
}

// 2. Открываем файл в режиме чтения ('r')
$handle = fopen($filePath, 'r');

if ($handle === false)
{
  fwrite(STDERR, "Error: Can\'t open file.\n");
  exit(1);
}

// 3. Читаем файл построчно до самого конца (EOF)
switch($chunkSize)
{
case -1: fpassthru($handle); break;
case 0:
  while (($line = fgets($handle)) !== false)
  {
    echo $line; // Выводим строку напрямую в консоль
    if($sleep)
      USleep($sleep);
  }
  break;
default:
  while (($chunk = fread($handle, $chunkSize)) !== false)
  {
    echo $chunk; // Выводим строку напрямую в консоль
    if($sleep)
      USleep($sleep);
  }
}

// 4. Обязательно закрываем дескриптор файла
fclose($handle);