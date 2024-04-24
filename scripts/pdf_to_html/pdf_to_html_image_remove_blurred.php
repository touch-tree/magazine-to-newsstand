<?php

class pdf_to_html_image_remove_blurred
{
    //#########################################################################
    static private function  calculateBlur($imagePath) 
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
        $varianceSum = 0;
        $pixelCount = 0;
    
        for ($x = 1; $x < $width - 1; $x++) {
            for ($y = 1; $y < $height - 1; $y++) {
                $pixelMatrix = [];
                for ($i = -1; $i <= 1; $i++) {
                    for ($j = -1; $j <= 1; $j++) {
                        $rgb = imagecolorat($image, $x + $i, $y + $j);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        $luminance = sqrt(0.299*$r*$r + 0.587*$g*$g + 0.114*$b*$b);
                        $pixelMatrix[$i + 1][$j + 1] = $luminance;
                    }
                }
    
                $laplacian = $pixelMatrix[1][1]*4 - $pixelMatrix[0][1] - $pixelMatrix[2][1] - $pixelMatrix[1][0] - $pixelMatrix[1][2];
                $varianceSum += $laplacian * $laplacian;
                $pixelCount++;
            }
        }
    
        $variance = $varianceSum / $pixelCount;
        return $variance;
    }
   

    //#########################################################################
    static public function process(&$obj):void
    {
        foreach ($obj['content'] as $index => $properties) 
        {
            if( $properties['tag'] !== "image" ) { continue; }
            $img = digi_pdf_to_html::$processFolder."/".$properties['content'];
            $blur  = self::calculateBlur($img);
            if ($blur < 10) { unset($obj['content'][$index]); }
        }
 
        $obj['content'] = array_values($obj['content']); //re-index data
    }
    //#####################################################################

}

?>