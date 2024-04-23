<?php

class pdf_to_html_text_remove_header
{

    static private $minTopDistance =  80; //min spacing from top  

    //#####################################################################



    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
    
        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['groupNumber'] > 0 )                 { continue; }  //ignore grouped items
            if($properties['top'] > self::$minTopDistance )     { continue; }  

            unset($obj['content'][$index]);
        } 
       
        
        $obj['content'] = array_values($obj['content']);//re-index data object

    }
    //#####################################################################

}

?>