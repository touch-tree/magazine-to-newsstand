<?php

class pdf_to_html_text_orphan_content
{

    //Put Orphan text with another part, but only if orpahn is not part of a group
    //#####################################################################

    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        $arrayBlocks = [];
        //-----------------------------------------------
        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['tag'] !== "text") { continue; }
           
            foreach ($arrayBlocks as $index2 => $properties2) 
            {
                    if(
                        $properties['groupNumber'] == 0 
                        && $properties['left'] >= $properties2['left'] 
                        && $properties['left'] <= ($properties2['left'] + $properties2['width'])
                        && $properties['top'] >= $properties2['top'] 
                        && $properties['top'] <= ($properties2['top'] + $properties2['height'])
                      )
                      {
                            $txt1 = sys::strtoupper($obj['content'][$index2]['content']);
                            $txt2 = $obj['content'][$index2]['content'];
                            if($txt1 === $txt2)
                            {
                                $properties['content'] = sys::strtoupper($properties['content']);      
                            }
                        
                           //merge texts
                           $obj['content'][$index2]['content'] .=  $properties['content'];
                           $obj['content'][$index2]['left']     =  min([$obj['content'][$index2]['left'],$properties['left']]);
                           $obj['content'][$index2]['top']      =  min([$obj['content'][$index2]['top'],$properties['top']]);

                            //calc new width
                            $finalLeft1 = $obj['content'][$index2]['left'] + $obj['content'][$index2]['width'];
                            $finalLeft2 = $properties['left'] + $properties['width'];
                            $obj['content'][$index2]['width'] = max([$finalLeft1,$finalLeft2]) - $obj['content'][$index2]['left'];

                            //calc new height
                            $finalTop1 = $obj['content'][$index2]['top'] + $obj['content'][$index2]['height'];
                            $finalTop2 = $properties['top'] + $properties['height'];
                            $obj['content'][$index2]['height'] = max([$finalTop1,$finalTop2]) - $obj['content'][$index2]['top'];
                           
                           unset($obj['content'][$index]);  
                      }
            }


            $arrayBlocks[$index] = $properties;
            
        }
        
        $obj['content'] = array_values($obj['content']); //re-index data object


    }
    //#####################################################################

}

?>