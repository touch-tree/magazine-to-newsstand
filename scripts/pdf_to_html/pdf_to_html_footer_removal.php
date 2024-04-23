<?php

class pdf_to_html_footer_removal
{

    static private  $maxBottomMargin =  60; //max spacing bottom and text

    //#####################################################################



    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
      
        $pageHeight = $obj['meta']['pageHeight'];
        $maxTop = $pageHeight - self::$maxBottomMargin;

        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['top'] < $maxTop )   {continue;}  
            unset($obj['content'][$index]);
        } 

        
        $obj['content'] = array_values($obj['content']); //re-index data object



 
    }
    //#####################################################################

}

?>