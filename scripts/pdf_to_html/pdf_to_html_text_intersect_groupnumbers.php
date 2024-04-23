<?php

class pdf_to_html_text_intersect_groupnumbers
{

  

    //#####################################################################
    static public function process(&$obj):void
    {	
        
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
  
        $arrayBlocks = [];
     
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
                            )
                            {
                                    $groupId1 = $properties['groupNumber'];  
                                    $groupId2 = $properties2['groupNumber'];  
                                    if($groupId1 > 0 and $groupId2>0) {continue;}

                                    if($groupId1 > 0)        {$groupId =  $groupId1;}
                                    elseif($groupId2 > 0)    {$groupId =  $groupId2;}
                                    else                     {$groupId =  digi_pdf_to_html::getNewGroupNumber($obj);}
                
                                    $obj['content'][$index]['groupNumber'] = $groupId;
                                    $obj['content'][$index2]['groupNumber'] = $groupId;
                            }
                    }

            $arrayBlocks[$index]=$properties;
            
        }

 
    }
    //#####################################################################

}

?>