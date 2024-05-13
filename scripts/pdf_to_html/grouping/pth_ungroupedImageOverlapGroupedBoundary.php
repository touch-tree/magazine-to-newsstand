<?php
declare(strict_types=1);

/*
    - group image-nodes that are still ungrouped but overlap another group-boundary 
*/

class pth_ungroupedImageOverlapGroupedBoundary
{    
   
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
        $imageNodes =        digi_pdf_to_html::returnProperties("tag","image",false);   //ungrouped images!
        $assignedGroups =    digi_pdf_to_html::returnAssignedGroups();
        $len =               sizeof($assignedGroups);

        $boundary = digi_pdf_to_html::returnGroupBoundary(1);
    
        
        for($n=0;$n<$len;$n++)
        {
            $boundary = digi_pdf_to_html::returnGroupBoundary($assignedGroups[$n]);
            
            foreach ($imageNodes as $index => $properties) 
            {

                if(digi_pdf_to_html::nodeOverlapsBoundary($properties,$boundary))
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