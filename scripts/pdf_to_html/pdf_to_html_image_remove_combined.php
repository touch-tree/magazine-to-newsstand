<?php
/*

    Detect if an image if fully embedded within another one. (note: ignore overlapping ones).
    When an image is fully embedded within an other one, it is a design helper-image, and can be removed.
    
*/
class pdf_to_html_image_remove_combined
{
    //#########################################################################

    static public function process(&$obj):void
    {
         //force sorting
         digi_pdf_to_html::sortByTopThenLeftAsc($obj);

         $arrayBlocks=          [];
         foreach ($obj['content'] as $index => $properties) 
         {
             if($properties['tag'] !== "image") { continue; }
 
                foreach ($arrayBlocks as $index2 => $properties2) 
                {
                        if(
                            $properties['left'] >= $properties2['left'] 
                            && $properties['top'] >= $properties2['top'] 
                            && ( $properties['left'] + $properties['width']  ) <= ( $properties2['left'] + $properties2['width'] )
                            && ($properties['top'] + $properties['height'])  <= ($properties2['top'] + $properties2['height'])
                        )
                        {
                                //delete image
                                unset($obj['content'][$index]);   
                                
                        }
                }
 
             $arrayBlocks[$index]=$properties;
         }

         $obj['content'] = array_values($obj['content']); //re-index data object

    }
    //#####################################################################

}

?>