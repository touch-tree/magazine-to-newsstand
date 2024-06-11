<?php
declare(strict_types=1);
/*
    Remove odd characters or content that must be removed
*/

class pth_removeStrangeTexts
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
        $textNodes = digi_pdf_to_html::returnProperties("tag","text");

        foreach( $textNodes as $index => $properties) 
        {
            $delete=false;

            //in-design rest code
            if(stristr($properties['content'],".indd"))        { $delete=true; }  
            
            //out of place (html) characters
            $htmlDecoded = sys::returnAlphaNum(html_entity_decode($properties['content']));
            if(sys::length( $htmlDecoded) == 0)                    { $delete=true; }    
            
            if($delete)
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