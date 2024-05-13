<?php
declare(strict_types=1);
//#####################################################################
/*

    Detect if an image if fully embedded within another one. (note: ignore overlapping ones).
    When an image is fully embedded within an other one, it is a design helper-image, and can be removed.
    
*/
//#####################################################################

class pth_removeOverlappingImages
{    

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
            $objBoundary = digi_pdf_to_html::returnBoundary([$index]);
            foreach( $imageNodes as $index2 => $properties2 ) 
            {
                if($properties === $properties2) { continue; }
                if(digi_pdf_to_html::nodeWithinBoundary($properties2,$objBoundary))
                {
                    
                    //remove the smallest image (assuming it is used as layout-helper)
                    $area1 = $properties['width'] *  $properties['height'];
                    $area2 = $properties2['width'] * $properties2['height'];
                    $removalIndex = $index;
                    if($area1 > $area2) { $removalIndex = $index2;}                    
                    digi_pdf_to_html::removeIndex($removalIndex);
                    $this->cleanup($obj);
                    return;
                }
            }
        }
    }

    //#####################################################################


    //#################################################################



}

?>