<?php

class pdf_to_html_remove_overlapping_images
{

    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
        $arrayBlocks = [];

        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['tag'] !== "image") { continue; }

        
            foreach ($arrayBlocks as $index2 => $properties2) 
            {
                    if(
                        $properties['left'] >= $properties2['left'] 
                        && $properties['left'] <= ($properties2['left'] + $properties2['width'])
                        && $properties['top'] >= $properties2['top'] 
                        && $properties['top'] <= ($properties2['top'] + $properties2['height'])
                      )
                      {
                            //delete image
                            unset($obj['content'][$index]);   
                      }
            }


            $arrayBlocks[]=$properties;
            
        }
        
        $obj['content'] = array_values($obj['content']); //re-index data object


    }
    //#####################################################################

}

?>