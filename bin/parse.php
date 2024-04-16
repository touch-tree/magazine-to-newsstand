<?php
include ("../yescrm6000/scripts/controller.php");
//######################################################
$path=          "D:/temp/target/output-9.html";
$html =         file_get_contents($path);
$dom =          new html_parser();
$dom->setFullHtml($html);
$body = $dom->tagName("body")[0];
//######################################################
$nodes = $dom->tagName('*');
$loop = $nodes->length;
//######################################################
function extractInteger($string)
{
    preg_match('/-?\d+/', $string, $matches);
    $integer = (int)$matches[0];
    return $integer;
}
//######################################################
$obj = [];

for($n=0; $n<$loop; $n++)
{
    if(!$dom->hasInitialproperty($nodes[$n],"position"))    {continue;}
    if(!$dom->hasInitialproperty($nodes[$n],"top"))         {continue;}
    if(!$dom->hasInitialproperty($nodes[$n],"left"))        {continue;}
    if( strlen($nodes[$n]->textContent)==0)                 {continue;}

    $arr=[];
    $arr['html'] =  $dom->innerHTML($nodes[$n]);
    $arr['top'] =   extractInteger($dom->returnInitialproperty($nodes[$n],"top"));
    $arr['left'] =  extractInteger($dom->returnInitialproperty($nodes[$n],"left"));
    if($arr['left'] < 0 || $arr['top'] < 0 )                { continue; }
    $obj[]=$arr;
}


usort($obj, function($a, $b) {
    if ($a['top'] == $b['top']) {
        return $a['left'] - $b['left'];
    } else {
        return $a['top'] - $b['top'];
    }
});

print_r($obj);exit;


echo $dom->outerHTML($body);









?>