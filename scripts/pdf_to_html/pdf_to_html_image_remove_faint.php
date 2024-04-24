<?php

class pdf_to_html_image_remove_faint
{
    //#########################################################################
    
    /* betweeen 0 (black) and 765 (white) */

    static private function isImageFaint($imagePath, $threshold = 765) 
    {
            $imageType = exif_imagetype($imagePath);
        
            switch($imageType) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($imagePath);
                    break;
                default:
                    return false;
            }
        
            $width = imagesx($image);
            $height = imagesy($image);
        
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
    //#########################################################################
    static public function process(&$obj):void
    {
       
        foreach ($obj['content'] as $index => $properties) 
        {
            if( $properties['tag'] !== "image" ) { continue; }
            $img = digi_pdf_to_html::$processFolder."/".$properties['content'];
            if(self::isImageFaint($img,600)) { unset($obj['content'][$index]); }  
        }
 

        $obj['content'] = array_values($obj['content']); //re-index data
    }
    //#####################################################################

}

?>