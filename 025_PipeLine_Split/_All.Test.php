<?
for($F=__FILE__; $F;) if(@include($F=DirName($F)).'/Using.php') break;
  
$Loader->GetLogger()->ScriptLog();
$Loader->AutoDispose();

$Dir=GetCwd();
ChDir(__DIR__);
Include '_All.php';
include 'Test/_All.php';

ChDir($Dir);
