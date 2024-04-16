<?php
include ("../yescrm6000/scripts/controller.php");
//######################################################
$baseCommand=   "C:/xampp/htdocs/poppler/pdftohtml";
$baseCommand=   "C:/Program Files/Calibre2/app/bin/pdftohtml"; $baseCommand = '"'.$baseCommand.'"';
$path =         "D:/tempPdfs\italie magazine 2021_2_iPAD.pdf";
$folder=        "C:/tmp/digidatabase/d787671046ec716d4d6011b9d4529ae4/";
$target =       $folder."output.html";
$page=          9;
//#####################################################
//clear target folder
files::removeFolder($folder);
files::createDir($folder);


$params = array(
    "fontfullname" =>        [null,null],
    "i" =>                   [null,null],
    "p" =>                   [null,null],
    "c" =>                   [null,null],
    "f" =>                   [$page," "],
    "l" =>                   [$page," "]
    );


$command =  $baseCommand.shell::extractParams($params).' '.escapeshellarg($path).' '.$target;
//echo "command: $command";exit;

$out = shell::command($command,$folder);
print_r($out);

$out = $folder."output-$page.html";
$out = file_get_contents($out);
echo $out;
//######################################################
$baseCommand=   "C:/xampp/htdocs/poppler/pdfimages.exe";


$params = array(
    "p" =>                   [null,null],
    "png" =>                 [null,null],
    "f" =>                   [$page," "],
    "l" =>                   [$page," "]
    );

$command =  $baseCommand.shell::extractParams($params).' '.escapeshellarg($path).' '.$target;
$out = shell::command($command,$folder);
print_r($out);
?>