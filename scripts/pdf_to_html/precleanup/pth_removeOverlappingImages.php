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

    public function __construct(&$obj)
    {
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        //-------------------------------
        $this->cleanup($obj);       
    }
    
    //#####################################################################
    private function cleanup(&$obj)
    {
        $imageNodes = digi_pdf_to_html::returnProperties($obj,"tag","image");
        foreach( $imageNodes as $index => $properties) 
        {
            $objBoundary = digi_pdf_to_html::returnBoundary($obj,[$index]);
            foreach( $imageNodes as $index2 => $properties2 ) 
            {
                if($properties === $properties2) { continue; }
                if(digi_pdf_to_html::nodeWithinBoundary($properties2,$objBoundary))
                {
                    digi_pdf_to_html::removeIndex($obj,$index2);
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