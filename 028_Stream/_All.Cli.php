<?
for($F=__FILE__; $F;) if(@include($F=DirName($F)).'/Using.php') break; unset($F);
  
$Loader->AutoDispose();
$Loader->Load_Lib('/Debug/Format');
$Loader->Load_Lib('/Log/Logger');
$Loader->GetLogger()->ScriptLog();

unset($Loader);

C_Debug_Template::$Closure_Use    =false;
C_Debug_Template::$Closure_This   =false;
C_Debug_Template::$Closure_Params =false;

(function() {
  //$argv, $argc, $_GET, $_POST, $_COOKIE, $_FILES, $_ENV, $_REQUEST, $_SERVER
  $globalsKeysInitial = Array_Flip(Array_Keys($GLOBALS));

  \WLib\Object\T_ShutDown::Register(function() use($globalsKeysInitial){
    // Запускаем Цикл Событий
    Loop::run();
    
    $globalsKeys = Array_Reverse(Array_Keys($GLOBALS));
    $removed = [];
    foreach ($globalsKeys As $key)
      if (!array_key_exists($key, $globalsKeysInitial))
      {
        $removed[] = $key;
        unset($GLOBALS[$key]);
      }
    if ($removed)
      Logger::debug('Global variables are evil: $', implode(', $', $removed));
  //if ($globalsKeysInitial)
  //  Logger::debug('Ignore global variables: $', implode(', $', array_keys($globalsKeysInitial)));
    
    // Вычисляем общее время выполнения
    $executionTime = Loop::getTime();
  
    if (true)
      Stat::debug();
    else
      Logger::log('It tooks ', round($executionTime, 2), ' seconds');
  });
  
  $Dir=GetCwd();
  ChDir(__DIR__);
  
  include '_All.php';
  include 'Test/_All.php';
  
  ChDir($Dir);
})();
