<?php
declare(strict_types=1);

/*
    - remove ungrouped white-coloured texts (usually lost words during development). These were not picked up in the pre-cleanup somehow.
*/

class pth_ungroupedInvisibleTexts
{    
   
    private $leftLevel = 5;

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
        $this->removeLostWhiteWords($obj);
    }
    //#####################################################################

    private function removeLostWhiteWords(&$obj)
    {
        $textNodes = digi_pdf_to_html::returnProperties("tag","text",false);

        foreach( $textNodes as $index => $properties) 
        {
            if($properties['fontColor'] === "#ffffff" && sys::length($properties['content']) < 20)
            {
                digi_pdf_to_html::removeIndex($index);
                $this->cleanup($obj);
                return;     
            }
        }
    }
        
     //#####################################################################


   
   


}

?>