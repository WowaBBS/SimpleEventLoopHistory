<?
$dontWrite=True;
$Hook_Using=
  Function($Point, &$FileName, &$Line, $Path)
  {
    $Line=0;
    $FileName=Explode('/',$FileName);
    If(Count($FileName)>1)
      Array_Shift($FileName);
    Else
      Array_UnShift($FileName, '..');
    $FileName=Implode('/',$FileName);
  };
$Hook_Folder=
  Function($ShorName)
  {
    If($ShorName==='History' ) Return False;
    If($ShorName==='.git'    ) Return False;
  };
Include '..\PhpFormatter\Src\App.php8';