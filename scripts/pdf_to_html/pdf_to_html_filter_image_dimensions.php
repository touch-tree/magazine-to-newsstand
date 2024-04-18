<?php

class pdf_to_html_filter_image_dimensions
{
    //#####################################################################
    //assume streched vertical and horizontal images are part of local design/layout and not relevant for html output
    //#####################################################################

    static private  $maxVerticalRatio =   2.5;
    static private  $maxHorizontalRatio = 3.5;

    static public function process(&$obj)
    {	
            $len = sizeof( $obj['content'] );  

            for($n=0; $n < $len; $n++)
            {
                    if( $obj['content'][$n]['tag'] !== "image" ) { continue; }

                    $img = digi_pdf_to_html::$processFolder."/".$obj['content'][$n]['content'];
                    images::detectImageDimensions($img);

                    if(!isset(images::$settings['imageWidth']) or sys::posInt(images::$settings['imageWidth']) == 0 )     { continue; }
                    if(!isset(images::$settings['imageHeight']) or sys::posInt(images::$settings['imageHeight']) == 0 )   { continue; }
          
                    //----------------------
                    $isDeletable = false;

                    $w = images::$settings['imageWidth'];
                    $h = images::$settings['imageHeight'];

                    if($w > $h)
                    {
                        $ratio = $w / $h;  
                        if($ratio > self:: $maxHorizontalRatio ) { $isDeletable = true; }  
                    }
                    else
                    {
                        $ratio = $h / $w;  
                        if($ratio > self:: $maxVerticalRatio )   { $isDeletable = true; }      
                    }

                    if($isDeletable)
                    {
                        unset($obj['content'][$n]);
                    }
 
            }

            $obj['content'] = array_values ($obj['content']);
    }
    //#####################################################################

}

?>