<?
$LargeFileName='large_test_file.txt';

if(Is_File($LargeFileName)) return;
file_put_contents($LargeFileName,
  str_repeat("Здесь много текста для проверки асинхронности. ", 50000))
;
