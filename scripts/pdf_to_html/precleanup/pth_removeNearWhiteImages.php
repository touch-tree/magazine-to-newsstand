<?php
declare(strict_types=1);
//#####################################################################


class pth_removeNearWhiteImages
{    

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
            if(in_array($properties['content'],$this->arrayhandledImages))   { continue; }
            $this->arrayhandledImages[]=$properties['content'];
            
            $img = digi_pdf_to_html::$processFolder."/".$properties['content'];
            if($this->isImageNearWhite($img,500)) 
            { 
                digi_pdf_to_html::removeIndex($index);
                $this->cleanup($obj);
                return;
            }  

        }
    }

    //#####################################################################
    private function isImageNearWhite($img,$threshold)
    {
        $image = images::returnImage($img);
        if(!isset($image)) { return ;}
        $width =  images::returnWidth($image);
        $height = images::returnHeight($image);

        for($x = 0; $x < $width; $x++) {
            for($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
    
                $totalColor = $r + $g + $b;
    
                if($totalColor < $threshold) {
                    return false;
                }
            }
        }
        return true;

    }
    //#################################################################



}

?>