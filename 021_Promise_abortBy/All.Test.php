<?
for($F=__FILE__; $F;) if(@include($F=DirName($F)).'/Using.php') break;
  
//$Loader->GetLogger()->Add($argv[0].'.log');
$info=PathInfo($argv[0]);
$logFile=$info['dirname'].'/'.$info['filename'];
$Loader->GetLogger()->Add($logFile.'.log');

include 'All.php';
