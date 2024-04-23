<?php

class pdf_to_html_remove_last_hyphen
{
    //remove last hyphen character, and this is used to continue text on a next line. Since we are merging texts this must be removed.
    //this class must be done before any other text-merger, as the hypen must be detected in the orignal content, and located as the last character

    /*
        for example source:

        Today I walked into a room and saw a data-
        base interface on my computer.

        target output must become:
        Today I walked into a room and saw a database interface on my computer.

    */
    //#####################################################################
    static public function process(&$obj):void
    {	
        $len = sizeof( $obj['content']);  
        for($n=0; $n < $len; $n++)
        {
            if($obj['content'][$n]['tag'] === "image")                                          { continue; }
            if(sys::length($obj['content'][$n]['content']) <= 1)                                { continue; }
            if(!sys::stringEndswith($obj['content'][$n]['content'],"-"))                        { continue; }
            $obj['content'][$n]['content'] = rtrim($obj['content'][$n]['content'], "-");
        }     
    }
    //#####################################################################

}

?>