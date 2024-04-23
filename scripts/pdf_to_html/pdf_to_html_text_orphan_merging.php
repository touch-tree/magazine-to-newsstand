<?php

class pdf_to_html_text_orphan_merging
{

    static public function process(&$obj):void
    {	
       
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
        $arrayBlocks = [];
        $arrayMerger = [];
     
        foreach ($obj['content'] as $index => $properties) 
        {
                    if($properties['tag'] === "image") { continue; }

                    foreach ($arrayBlocks as $index2 => $properties2) 
                    {
                            if(
                                $properties['left'] >= $properties2['left'] 
                                && $properties['left'] <= ($properties2['left'] + $properties2['width'])
                                && $properties['top'] >= $properties2['top'] 
                                && $properties['top'] <= ($properties2['top'] + $properties2['height'])
                                && $properties2['height'] >= $properties['height'] /* make sure base-component is higher */
                            )
                            {
                                    $arrayMerger[$index2] = $index;
                            }
                    }

            $arrayBlocks[$index]=$properties;
            
        }

        foreach ($arrayMerger as $baseIndex => $targetIndex) 
        {
            digi_pdf_to_html::mergeBlocks($obj,$baseIndex,$targetIndex,false);   
        }

        $obj['content'] = array_values($obj['content']); //re-index data

 
    }
    //#####################################################################

}

?>