<?php
declare(strict_types=1);

class pth_removeImageNearWhite
{
    public function __construct(&$obj)
    {
        foreach ($obj['content'] as $index => $properties) 
        {
            if( $properties['tag'] !== "image" ) { continue; }
            $img = digi_pdf_to_html::$processFolder."/".$properties['content'];
            if($this->isImageNearWhite($img,500)) { unset($obj['content'][$index]); }  
        }
 
        $obj['content'] = array_values($obj['content']); //re-index data   

    }

    //########################################################
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
 //########################################################



    

}

?>