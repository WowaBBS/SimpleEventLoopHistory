<?
(function() {
  $startTime = microtime(true);
  $globalsKeysInitial=Array_Flip(Array_Keys($GLOBALS));

  register_shutdown_function(function() use($startTime, $globalsKeysInitial){
    // Запускаем Цикл Событий
    Loop::run();
    
    $globalsKeys=Array_Reverse(Array_Keys($GLOBALS));
    $removed=[];
    foreach ($globalsKeys As $key)
      if (!array_key_exists($key, $globalsKeysInitial))
      {
        $removed[]=$key;
        unset($GLOBALS[$key]);
      }
    if ($removed)
      Logger::debug('Global variables are evil: $', implode(', $', $removed));
    
    $endTime = microtime(true);
  
    // Вычисляем общее время выполнения
    $executionTime = $endTime - $startTime;
  
    Logger::log('It took ', Round($executionTime, 2), ' seconds');
  
    \WLib\Object\C_Loader::Get()->Dispose();
  });
  
  $Dir=GetCwd();
  ChDir(__DIR__);
  
  include '_All.php';
  include 'Test/_All.php';
  
  ChDir($Dir);
})();

for($F=__FILE__; $F;) if(@include($F=DirName($F)).'/Using.php') break; unset($F);
  
unset($Loader);
\WLib\Object\C_Loader::Get()->GetLogger()->ScriptLog();

C_Debug_Template::$Closure_Use    =false;
C_Debug_Template::$Closure_This   =false;
C_Debug_Template::$Closure_Params =false;
