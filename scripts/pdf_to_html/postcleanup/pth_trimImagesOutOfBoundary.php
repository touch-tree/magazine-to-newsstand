<?php
declare(strict_types=1);

/*
    For final sorting-purposes it is necessary that the text-nodes are leading. 
    Images that exceed top,bottom,left and right should be confined within text-nodes coordinates. Such image are out of bound usually for design purposes.
    These images must be trimmed to fit these dimensions.
*/

class pth_trimImagesOutOfBoundary
{    
    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();
        //-------------------------------
        $this->execute($obj);       
    }
    
    //#####################################################################
    private function execute(&$obj)
    {
        $textNodes =    digi_pdf_to_html::returnProperties("tag","text");
        $imageNodes =   digi_pdf_to_html::returnProperties("tag","image");
        $keys =         array_keys($textNodes);
        $minTop =       digi_pdf_to_html::returnMinMaxProperyValue("top", $keys , false);
        $minLeft =      digi_pdf_to_html::returnMinMaxProperyValue("left", $keys , false);

        foreach ($imageNodes as $index => $properties) 
        {
            if($properties['top'] < $minTop)
            {
                $diff = $minTop - $properties['top'];
                $obj['nodes'][$index]['top'] = $minTop;
                $obj['nodes'][$index]['height'] = $obj['nodes'][$index]['height'] - $diff;
            }

            if($properties['left'] < $minLeft)
            {
                $diff = $minLeft - $properties['left'];
                $obj['nodes'][$index]['left'] = $minLeft;
                $obj['nodes'][$index]['width'] = $obj['nodes'][$index]['width'] - $diff;
            }
        }
    }
    //#####################################################################


}

?>