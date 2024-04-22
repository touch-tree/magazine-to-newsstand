<?php

class pdf_to_html_filter_image_dimensions
{
    //#####################################################################
    /*
        - Removes stretched images that are mostlt used for styling purposes in a pdf. These should not appear in an html version.
        - The ratio differs between a vertical and horizontal one.
    */

    //#####################################################################

    static private  $maxVerticalRatio =   2.5;
    static private  $maxHorizontalRatio = 3.5;
    static private  $maxImageWidth=       800;

    static public function process(&$obj):void
    {	
            $len = sizeof( $obj['content'] );  

            for($n=0; $n < $len; $n++)
            {
                    if( $obj['content'][$n]['tag'] !== "image" ) { continue; }

                    //--------------
                    //read image data
                    $img = digi_pdf_to_html::$processFolder."/".$obj['content'][$n]['content'];
                    images::detectImageDimensions($img);
                    if(!isset(images::$settings['imageWidth']) or sys::posInt(images::$settings['imageWidth']) == 0 )     { continue; }
                    if(!isset(images::$settings['imageHeight']) or sys::posInt(images::$settings['imageHeight']) == 0 )   { continue; }
          
                    //----------------------
                    //determine ratio
                    $isDeletable = false;

                    $w = images::$settings['imageWidth'];
                    $h = images::$settings['imageHeight'];

                    if($w > $h)
                    {
                        //hotizontal
                        $ratio = $w / $h;  
                        if($ratio > self:: $maxHorizontalRatio ) { $isDeletable = true; }  
                    }
                    else
                    {
                        //vertical
                        $ratio = $h / $w;  
                        if($ratio > self:: $maxVerticalRatio )   { $isDeletable = true; }      
                    }

                    if($isDeletable)
                    {
                        unset($obj['content'][$n]);
                    }
                    else
                    {
                            //----------------------
                            //resize large images
                            if($w > self::$maxImageWidth)
                            {
                                images::resizeImage($img,self::$maxImageWidth);
                            }

                    }
            }

            $obj['content'] = array_values ($obj['content']); //re-index all data
    }
    //#####################################################################

}

?>