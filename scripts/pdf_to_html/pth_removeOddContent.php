<?php
declare(strict_types=1);
    /*
        Remove odd characters or content that must be removed

    */

class pth_removeOddContent
{
    public function __construct(&$obj)
    {
        
        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['tag'] === "image")             { continue; }
            $delete=false;

             //in-design rest code
             if(stristr($properties['content'],".indd"))    { $delete=true; }  

            //out of place (html) characters
            $htmlDecoded = sys::returnAlphaNum(html_entity_decode($properties['content']));
            if(sys::length( $htmlDecoded) == 0)                    { $delete=true; }    
            
            if($delete)
            {
                unset($obj['content'][$index]);
            }
        }

        $obj['content'] = array_values ($obj['content']); //re-index all dat    
    }

}

?>