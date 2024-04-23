<?php

class pdf_to_html_remove_odd_content
{
    /*
        Remove odd characters or content that must be removed

    */
    //#####################################################################
    static public function process(&$obj):void
    {	
          
        $len = sizeof( $obj['content']);  
        for($n=0; $n < $len; $n++)
        {
            if($obj['content'][$n]['tag'] === "image")             { continue; }
            $delete=false;

            //in-design rest code
            if(stristr($obj['content'][$n]['content'],".indd"))    { $delete=true; }  

            //odd (html) characters
            $htmlDecoded = sys::returnAlphaNum(html_entity_decode($obj['content'][$n]['content']));
            if(sys::length( $htmlDecoded) == 0)                    { $delete=true; }                 

            if($delete)
            {
                unset($obj['content'][$n]);
            }
        }

        $obj['content'] = array_values ($obj['content']); //re-index all data

    }
    //#####################################################################

}

?>