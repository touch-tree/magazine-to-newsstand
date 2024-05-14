<?php
declare(strict_types=1);

/*
    - group image-nodes with a group-boundary, when the image is left a certain boundary area
*/

class pth_ungroupedImageLeftFromGroupedBoundary
{    
   
    private $leftLevel = 20;

    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();
        //-------------------------------
        $this->execute($obj);       
    }
    
    //#####################################################################

    private function execute(&$obj)
    {
        $assignedGroups =    digi_pdf_to_html::returnAssignedGroups();
        $len =               sizeof($assignedGroups);
        $imageNodes =        digi_pdf_to_html::returnProperties("tag","image",false);
                
        for($n=0;$n<$len;$n++)
        {
            $boundary = digi_pdf_to_html::returnGroupBoundary($assignedGroups[$n]);
            
            foreach ($imageNodes as $index => $properties) 
            {
                if(!digi_pdf_to_html::nodeOverlapsBoundary($properties,$boundary)) { continue; }
                
                $boundary2 = digi_pdf_to_html::returnBoundary([$index]);
                if($boundary['maxLeft'] <  $boundary2['maxLeft']) {continue;} /* image must be aligned on the let side only */
                $leftLevel = $boundary2['maxLeft'] - $boundary['left'];
                if($leftLevel >= $this->leftLevel )
                {
                    //get index from any nodes from this group
                    $groupNodes = digi_pdf_to_html::returnProperties("groupNumber", $assignedGroups[$n],true);
                    $index2 = array_keys($groupNodes)[0];

                    $grouped = digi_pdf_to_html::groupNodes([$index,$index2]);
                    if($grouped)
                    {
                        $this->execute($obj);  
                        return;
                    }

                }
               
            }
        }
    }

     //#####################################################################


   
   


}

?>