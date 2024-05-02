<?php
declare(strict_types=1);
/*
    find it text-nodes have dummy text assigned to the data - object:
    - similar coloured font with the same background
*/

class pth_removeInvisibleTexts
{    
    private $imageWidth =   null;
    private $imageHeight =  null;
    private $image = null;
    
    public function __construct(&$obj)
    {
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        //-------------------------------
        //obtain image data
        $this->image = $this->returnImageFromPage($obj['meta']['pageNumber'] );
        if(!isset($this->image)) { return ; } 
        $this->imageWidth =   images::returnWidth($this->image);
        $this->imageHeight =  images::returnHeight($this->image);
        $this->cleanup($obj);       
    }
    
    //#####################################################################
    private function cleanup(&$obj)
    {
        $textNodes = digi_pdf_to_html::returnProperties($obj,"tag","text");

        foreach( $textNodes as $index => $properties) 
        {
            $fontColor = $properties['fontColor'];
            $block =     digi_pdf_to_html::returnBoundary($obj,[$index]);

            //-------------------------------------
            //start block location
            $pixelTop =     sys::posInt(round(($block['pagePercentageStartTop']/100) * $this->imageHeight));
            $pixelLeft =    sys::posInt(round(($block['pagePercentageStartLeft']/100) * $this->imageWidth));
            $color =        images::getPixelColour($this->image,$pixelTop,$pixelLeft);
            
            $delete =       false;
            if( isset($color) && colours::colourIsSimilar($fontColor,$color)) { $delete =  true; }

            //------------------------------------
            //end block location
            if(!$delete )
            {
                $pixelTop =     sys::posInt(round(($block['pagePercentageEndTop']/100) * $this->imageHeight));
                $pixelLeft =    sys::posInt(round(($block['pagePercentageEndLeft']/100) * $this->imageWidth));
                $color =        images::getPixelColour($this->image,$pixelTop,$pixelLeft);
                if( isset($color) && colours::colourIsSimilar($fontColor,$color)) {  $delete =  true; } 
            }

            //----------------------------------
            //delete node
            if($delete)
            {
                digi_pdf_to_html::removeIndex($obj,$index);
                $this->cleanup($obj);
                return;
            }
            //--------------------------------
        }
    }

    //#####################################################################
    private function returnImageFromPage(int $pageNumber)
    {
        $articleId =    digi_pdf_to_html::$articleId;
        if(!isset($articleId)) { return null;}

        $name =     settings::server()['digital']['articles']['nameBase']; //use 'nameBase' for full-resolution image (mybe slower to process though).else use 'nameMedium'
        $objFile =  new file_manager(); 
        $path =     files::standardizePath(settings::server()['digital']['folderArticleImages']."/".$articleId."/".$name.$pageNumber.".jpg");
        $path = $objFile->realPath($path);
        if(!is_file($path)) { return null;}
        return images::returnImage($path);
    }
    //#####################################################################

}

?>