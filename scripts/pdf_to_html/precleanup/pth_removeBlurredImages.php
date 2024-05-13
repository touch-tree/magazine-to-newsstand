<?php
declare(strict_types=1);
//#####################################################################


class pth_removeBlurredImages
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
            $blur  = $this->calculateBlur($img);
            if ($blur < 20) 
            {
                digi_pdf_to_html::removeIndex($index);
                $this->cleanup($obj);
                return;
            }

        }
    }

    //#####################################################################

    private function calculateBlur($img)
    {

            $image = images::returnImage($img);
            if(!isset($image)) { return ;}
            
            $width =  images::returnWidth($image);
            $height = images::returnHeight($image);

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

    //#################################################################



}

?>