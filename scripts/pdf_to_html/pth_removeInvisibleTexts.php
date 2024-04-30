<?php
declare(strict_types=1);
/*
    find it text-nodes have dummy text assigned to the data - object:
    - similar coloured font with the same background
*/

class pth_removeInvisibleTexts
{    
    public function __construct(&$obj)
    {
        //-------------------------------
        //obtain image data
        $image = $this->returnImage($obj);
        if(!isset($image)) { return ; } 
        $imageWidth =   images::returnWidth($image);
        $imageHeight =  images::returnHeight($image);
        
        //-------------------------------
        //loop text nodes
        foreach( $obj['content'] as $index => $properties) 
        {
            if($properties['tag'] === "image") { continue ;}
            if(!isset($properties['fontId']) or !isset(digi_pdf_to_html::$arrayFonts[$properties['fontId']]) ) { continue; } 
            $fontColor = digi_pdf_to_html::$arrayFonts[$properties['fontId']]['color'];
            $block =     digi_pdf_to_html::blockPositioning($obj,$index);

            //-------------------------------------
            //start block location
            $pixelTop =     sys::posInt(round(($block['pagePercentageStartTop']/100) * $imageHeight));
            $pixelLeft =    sys::posInt(round(($block['pagePercentageStartLeft']/100) * $imageWidth));
            $color =        images::getPixelColour($image,$pixelTop,$pixelLeft);
            
            if( isset($color) && colours::colourIsSimilar($fontColor,$color))
            {
                unset($obj['content'][$index]);
            }

            //------------------------------------
            //end location
            if(isset($obj['content'][$index]))
            {
                $pixelTop =     sys::posInt(round(($block['pagePercentageEndTop']/100) * $imageHeight));
                $pixelLeft =    sys::posInt(round(($block['pagePercentageEndLeft']/100) * $imageWidth));
                $color =        images::getPixelColour($image,$pixelTop,$pixelLeft);
                if( isset($color) && colours::colourIsSimilar($fontColor,$color))
                {
                    unset($obj['content'][$index]);
                }  

            }   
        }

        $obj['content'] = array_values ($obj['content']); //re-index all data
        
    }
    
    //#####################################################################

    private function returnImage(&$obj)
    {
        $articleId =    digi_pdf_to_html::$articleId;
        $pageNumber =   $obj['meta']['pageNumber'];
        if(!isset($articleId)) { return null;}

        $name =   settings::server()['digital']['articles']['nameBase']; //use 'nameBase' for full-resolution image (mybe slower to process though).else use 'nameMedium'
        $objFile =      new file_manager(); 
        $path =   files::standardizePath(settings::server()['digital']['folderArticleImages']."/".$articleId."/".$name.$pageNumber.".jpg");
        $path = $objFile->realPath($path);
        if(!is_file($path)) { return null;}
        return images::returnImage($path);
    }

    //#####################################################################


}

?>