<?php
declare(strict_types=1);
//############################################################################
class colours 
{
	static public function  isAlmostWhite($color, $threshold=250) {$color = sys::trim(str_replace('#', '', $color)); if(strlen($color) != 6){sys::error("Invalid hex color");} $r = hexdec(substr($color, 0, 2)); $g = hexdec(substr($color, 2, 2)); $b = hexdec(substr($color, 4, 2)); return ($r > $threshold && $g > $threshold && $b > $threshold);}

 

}

?>