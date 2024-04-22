<?php

class pdf_to_html_text_remove_footer
{

    static private  $maxBottomDistance =  60; //max spacing bottom and text

    //#####################################################################



    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);

        $pageHeight = $obj['meta']['pageHeight'];
        $maxTop = $pageHeight - self::$maxBottomDistance;

        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['groupNumber'] > 0 ) {continue;}  //ignore grouped items
            if($properties['top'] < $maxTop )   {continue;}  

            unset($obj['content'][$index]);
        } 

        
        $obj['content'] = array_values($obj['content']);//re-index data object

    }
    //#####################################################################

}

?>