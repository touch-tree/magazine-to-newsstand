<?php

class pdf_to_html_remove_odd_content
{


    //#####################################################################
    static public function process($page)
    {	
            
        $obj = digi_pdf_to_html::$arrayPages[$page]['content'];
        $len = sizeof( $obj );  
    
        for($n=0; $n < $len; $n++)
        {
            if($obj[$n]['tag'] === "image")             { continue; }
            $delete=false;
            if(stristr($obj[$n]['content'],".indd"))    {$delete=true;}

            if($delete)
            {
                unset(digi_pdf_to_html::$arrayPages[$page]['content'][$n]);
            }
        }

        digi_pdf_to_html::$arrayPages[$page]['content'] = array_values (digi_pdf_to_html::$arrayPages[$page]['content']);

    }
    //#####################################################################

}

?>