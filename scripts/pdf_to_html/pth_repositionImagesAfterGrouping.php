<?php
declare(strict_types=1);
/*
        Calculates the new top-position for the image after a grouped section
    
*/

class pth_repositionImagesAfterGrouping
{
    public function __construct(&$obj)
    {
          

            //force sorting
            digi_pdf_to_html::sortByTopThenLeftAsc($obj);
                                
            //get groups properties
            $objGroups = [];
            foreach ($obj['content'] as $index => $properties) 
            {
                    $groupId = $properties['groupNumber'];

                    if($groupId == 0) { continue; } 
                    if(!isset($objGroups[$groupId])) 
                    {
                        $objGroups[$groupId]=[];
                        $objGroups[$groupId]['finalTop']=   0;
                        $objGroups[$groupId]['minLeft']=       0;
                    }
                    
                    $finalTop = $properties['top'] + $properties['height'];
                    $left =     $properties['left'];

                    if($finalTop >  $objGroups[$groupId]['finalTop'] ) 
                    {
                        $objGroups[$groupId]['finalTop'] = $finalTop;
                    }

                    if($objGroups[$groupId]['minLeft'] == 0 or $properties['left'] < $objGroups[$groupId]['minLeft'] )
                    {
                        $objGroups[$groupId]['minLeft'] = $left;     
                    }
            }

            //relocate the image (not part of a group)
            foreach ($obj['content'] as $index => $properties) 
            {
                    if( $properties['tag'] !== "image") { continue; }
                    if( $properties['groupNumber'] > 0) { continue; }

                    foreach ($objGroups as $groupId => $props) 
                    {
                    
                        if($properties['top'] >= $props['finalTop'] and  $props['minLeft'] <= $properties['left']   ) 
                        {
                            $obj['content'][$index]['top'] =  $props['finalTop']; 
                        }
                    }

            }



        
    }

}

?>