<?php
declare(strict_types=1);
//#####################################################################
/*
    - Removes stretched images that are mostlt used for styling purposes in a pdf. These should not appear in an html version.
    - The ratio differs between a vertical and horizontal one.
*/

//#####################################################################

class pth_removeStrangeSizedImages
{    
    private  $maxVerticalRatio =   2.7;
    private  $maxHorizontalRatio = 3.5;
    private  $maxImageWidth=       800;
    private  $arrayhandledImages=  [];
    
    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();
        //-------------------------------
        $this->cleanup($obj);       
    }
    
    //#####################################################################
    private function cleanup(&$obj)
    {
        $imageNodes = digi_pdf_to_html::returnProperties("tag","image");

        foreach( $imageNodes as $index => $properties) 
        {
            if(in_array($properties['content'],$this->arrayhandledImages))                                        { continue; }
            $this->arrayhandledImages[]=$properties['content'];
            //--------------
            //read image data
            $img = digi_pdf_to_html::$processFolder."/".$properties['content'];
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
            if($h < 25)             { $isDeletable = true; }
            if($w < 90 && $w <> $h) { $isDeletable = true; }

            if(!$isDeletable && $w > $this->maxImageWidth )
            {
                images::resizeImage($img,$this->maxImageWidth);
            }

            if($isDeletable)
            {
                digi_pdf_to_html::removeIndex($index);
                $this->cleanup($obj);
                return;
            }


        }
    }

    //#####################################################################


}

?>