<?php
declare(strict_types=1);

/*
    - grouop text-nodes that are still ungrouped but overlap another group-boundary 
*/

class pth_ungroupedTextOverlapGroupedBoundary
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
        $textNodes =        digi_pdf_to_html::returnProperties($obj,"tag","text",false);   //ungrouped texts!
        $assignedGroups =   digi_pdf_to_html::returnAssignedGroups($obj);
        $len =              sizeof($assignedGroups);
        for($n=0;$n<$len;$n++)
        {
            $boundary = digi_pdf_to_html::returnGroupBoundary($obj,$assignedGroups[$n]);
            
            foreach ($textNodes as $index => $properties) 
            {
                if(digi_pdf_to_html::nodeOverlapsBoundary($properties,$boundary))
                {
                    //get index from any nodes from this group
                    $groupNodes = digi_pdf_to_html::returnProperties($obj,"groupNumber", $assignedGroups[$n],true);
                    $index2 = array_keys($groupNodes)[0];
                    $grouped = digi_pdf_to_html::groupNodes($obj,[$index,$index2]);
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