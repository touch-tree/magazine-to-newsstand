<?php

class pdf_to_html_remove_odd_content
{

    //#####################################################################
    static public function process(&$obj)
    {	
          

        $len = sizeof( $obj['content']);  
        for($n=0; $n < $len; $n++)
        {
            if($obj['content'][$n]['tag'] === "image")             { continue; }
            $delete=false;
            if(stristr($obj['content'][$n]['content'],".indd"))    {$delete=true;}

            if($delete)
            {
                unset($obj['content'][$n]);
            }
        }

        $obj['content'] = array_values ($obj['content']);

    }
    //#####################################################################

}

?>