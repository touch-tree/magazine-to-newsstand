<?php
declare(strict_types=1);

/*
    - group image-nodes that are still ungrouped but overlap another group-boundary 
*/

class pth_ungroupedImageOverlapBoundary
{    
   
    public function __construct(&$obj)
    {
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);

        //-------------------------------
        $this->execute($obj);       
    }
    
    //#####################################################################

    private function execute(&$obj)
    {
        $imageNodes =        digi_pdf_to_html::returnProperties($obj,"tag","image",false);   //ungrouped images!
        $assignedGroups =    digi_pdf_to_html::returnAssignedGroups($obj);
        $len =               sizeof($assignedGroups);
        for($n=0;$n<$len;$n++)
        {
            $boundary = digi_pdf_to_html::returnGroupBoundary($obj,$assignedGroups[$n]);
            
            foreach ($imageNodes as $index => $properties) 
            {
                if(digi_pdf_to_html::nodeOverlapsBoundary($properties,$boundary))
                {
                    //get index from any nodes from this group
                    $groupNodes = digi_pdf_to_html::returnProperties($obj,"groupNumber", $assignedGroups[$n],true);
                    $index2 = array_keys($groupNodes)[0];
                    digi_pdf_to_html::groupNodes($obj,[$index,$index2]);
                }
            }
        }
     
    }

     //#####################################################################


   
   


}

?>