<?
for($F=__FILE__; $F;) if(@include($F=DirName($F)).'/Using.php') break;
  
$Loader->GetLogger()->ScriptLog();

C_Debug_Template::$Closure_Use    =false;
C_Debug_Template::$Closure_This   =false;
C_Debug_Template::$Closure_Params =false;

$startTime = microtime(true);

register_shutdown_function(function() use($Loader, $startTime){
  // Запускаем Цикл Событий
  Loop::run();
  
  $endTime = microtime(true);

  // Вычисляем общее время выполнения
  $executionTime = $endTime - $startTime;

  Logger::log('It took ', Round($executionTime, 2), ' seconds');

  $Loader->Dispose();
});

$Dir=GetCwd();
ChDir(__DIR__);

include '_All.php';
include 'Test/_All.php';

ChDir($Dir);
