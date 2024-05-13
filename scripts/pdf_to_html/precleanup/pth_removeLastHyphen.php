<?php
declare(strict_types=1);

//remove last hyphen character, and this is used to continue text on a next line. Since we are merging texts this must be removed.
//this class must be done before any other text-merger, as the hypen must be detected in the orignal content, and located as the last character

/*
    for example source:

    Today I walked into a room and saw a data-
    base interface on my computer.

    target output must become:
    Today I walked into a room and saw a database interface on my computer.

*/

class pth_removeLastHyphen
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
            if(sys::length($properties['content']) <= 1)                    { continue; }
            if(!sys::stringEndswith($properties['content'],"-"))            { continue; }
            $obj['nodes'][$index]['content'] = rtrim($obj['nodes'][$index]['content'], "-"); 
        }
    }

    //#####################################################################


}

?>