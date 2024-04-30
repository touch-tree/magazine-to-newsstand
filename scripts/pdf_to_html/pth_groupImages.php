<?php
declare(strict_types=1);
/*
        Calculates the new top-position for the image after a grouped section
    
*/

class pth_groupImages
{
    
    private  $maxLeftMargin =       3;

    public function __construct(&$obj)
    {
          
            
            //force sorting
            digi_pdf_to_html::sortByTopThenLeftAsc($obj);

             //---------------------------------------------------
            //gather groupNumbers together in new object(s)
            $objGroups = [];
            foreach ($obj['content'] as $index => $properties) 
            {
                if($properties['groupNumber'] == 0)                           { continue; }
                if(!isset($objGroups[$properties['groupNumber']])){ $objGroups[$properties['groupNumber']]=[];}
                
                $objGroups[$properties['groupNumber']][$index] = $properties;         
            }

            $objGroups = array_values($objGroups);

            //--------------------------------------------------
            //traverse the gathers groups, and get the most comon fontId
            foreach ($objGroups as $key => $nodes) 
            {
                $indexes =        array_keys($nodes);
                $objBoundary =    digi_pdf_to_html::getTextBoundaryBlock($obj,$indexes);

                foreach ($obj['content'] as $index => $properties) 
                {
                    if($properties['groupNumber'] > 0)                                  { continue; }
                    if($properties['tag'] === "text")                                   { continue; }
                    if(!$this->blockInBoundary($properties,$objBoundary))               { continue; }  
                    
                    $obj['content'][$index]['groupNumber'] = $obj['content'][$indexes[0]]['groupNumber']; 
                }
            }        
    }

    //##########################################################################################
    private function blockInBoundary(array $properties, array $objBoundary):bool
    {        
        if(
            ( $properties['left'] + $this->maxLeftMargin ) < $objBoundary['left'] 
            || $properties['left'] > $objBoundary['maxLeft']
            || $properties['top']  <  $objBoundary['top']
            || $properties['top']  >  $objBoundary['maxTop'] 
          ) { return false; }
          return true;
    }
    //##########################################################################################

}

?>