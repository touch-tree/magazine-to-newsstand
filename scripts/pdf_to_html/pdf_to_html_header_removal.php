<?php

class pdf_to_html_header_removal
{

    static private  $maxTopMargin =  60; //max spacing top and first content

    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
      
        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['top'] > self::$maxTopMargin )     { continue; }  
            unset($obj['content'][$index]);
        } 
       
        $obj['content'] = array_values($obj['content']);//re-index data object

    }
    //#####################################################################

}

?>