<?
for($F=__FILE__; $F;) if(@include($F=DirName($F)).'/Using.php') break;
  
$Loader->GetLogger()->ScriptLog();
$Loader->AutoDispose();

include 'All.php';
