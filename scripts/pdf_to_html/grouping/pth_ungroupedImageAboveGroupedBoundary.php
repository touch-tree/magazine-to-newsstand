<?php
declare(strict_types=1);

/*
    - group image-nodes with a group-boundary, when the image is above a certain boundary area
    - the image is considered wider than the text beneath it
*/

class pth_ungroupedImageAboveGroupedBoundary
{    
   
    private $marginY =          30;
    private $marginCenter =     10;
    private $marginOffsetX =    5;

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
            $boundary =     digi_pdf_to_html::returnGroupBoundary($assignedGroups[$n]);
            $groupCenter =  round(($boundary['maxLeft'] + $boundary['left']) / 2);
            
            foreach ($imageNodes as $index => $properties) 
            {
                $boundary2 = digi_pdf_to_html::returnBoundary([$index]);
                $imageCenter = round(($boundary2['maxLeft'] + $boundary2['left']) / 2);

                if( abs($boundary2['maxTop'] - $boundary['top']) > $this->marginY  )    { continue; }
                if( abs($groupCenter - $imageCenter) > $this->marginCenter )            
                {
                     
                    if(abs($boundary2['left'] - $boundary['left'] ) > $this->marginOffsetX )
                    {
                        continue; 
                    }
                   
                }

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

     //#####################################################################


   
   


}

?>