<?php
declare(strict_types=1);


//#####################################################################
/*
    - Removes stretched images that are mostlt used for styling purposes in a pdf. These should not appear in an html version.
    - The ratio differs between a vertical and horizontal one.
*/

//#####################################################################



class pth_removeImageOddDimensions
{
    
    private  $maxVerticalRatio =   2.5;
    private  $maxHorizontalRatio = 3.5;
    private  $maxImageWidth=       800;

    public function __construct(&$obj)
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
                    if($ratio > $this->maxHorizontalRatio ) { $isDeletable = true; }  
                }
                else
                {
                    //vertical
                    $ratio = $h / $w;  
                    if($ratio > $this->maxVerticalRatio )   { $isDeletable = true; }      
                }

                //crap images
                if($h < 25) { $isDeletable = true; }

                if($isDeletable)
                {
                    unset($obj['content'][$n]);
                }
                else
                {
                        //----------------------
                        //resize large images
                        if($w > $this->maxImageWidth)
                        {
                            images::resizeImage($img,$this->maxImageWidth);
                        }

                }
        }

        $obj['content'] = array_values ($obj['content']); //re-index all data    




    }

    

}

?>