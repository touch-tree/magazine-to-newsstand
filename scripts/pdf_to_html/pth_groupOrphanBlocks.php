<?php
declare(strict_types=1);
/*
    find blocks that do NOT have group number yet, but may be related to a block with groupNumber
*/

class pth_groupOrphanBlocks
{
    private  $maxBlockYSeparator =  30;
    private  $maxLeftMargin =       3;
    private  $onlytext =            false;
    
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

        
            //---------------------------------------------------
            //find items ABOVE a boundary
            foreach ($objGroups as $key => $nodes) 
            {
                  $indexes =        array_keys($nodes);
                  $objBoundary =    digi_pdf_to_html::getTextBoundaryBlock($obj,$indexes);

                  foreach ($obj['content'] as $index => $properties) 
                  {
                        if($properties['groupNumber'] > 0)                                  { continue; }
                        if($this->onlytext && $properties['tag'] === "image")               { continue; }

                        $topMax = $properties['top'] + $properties['height'];
                        $left   = $properties['left'];

                        if( abs($objBoundary['top'] -$topMax) > $this->maxBlockYSeparator  ) { continue; }
                        if( abs($objBoundary['left'] -$left) > $this->maxLeftMargin  )       { continue; }

                        $obj['content'][$index]['groupNumber'] = $obj['content'][$indexes[0]]['groupNumber'];
                        $objGroups[$key][$index]=$properties;
                        $indexes[]=$index;
                        $objBoundary = digi_pdf_to_html::getTextBoundaryBlock($obj,$indexes);
                  }
            }
             //---------------------------------------------------
             //find items BELOW a boundary
             foreach ($objGroups as $key => $nodes) 
             {
                   $indexes =        array_keys($nodes);
                   $objBoundary =    digi_pdf_to_html::getTextBoundaryBlock($obj,$indexes);

                   foreach ($obj['content'] as $index => $properties) 
                   {
                         if($properties['groupNumber'] > 0)                                  { continue; }
                         if($this->onlytext && $properties['tag'] === "image")               { continue; }
 
                         if( abs($properties['top'] - $objBoundary['maxTop']) > $this->maxBlockYSeparator  ) { continue; }
                         if( abs($objBoundary['left'] -$properties['left']) > $this->maxLeftMargin  )       { continue; }
 
                         $obj['content'][$index]['groupNumber'] = $obj['content'][$indexes[0]]['groupNumber'];

                         $objGroups[$key][$index]=$properties;
                         $indexes[]=$index;
                         $objBoundary = digi_pdf_to_html::getTextBoundaryBlock($obj,$indexes);
                   }
             }
             //-----------------------------------------------------
          
    }

}

?>