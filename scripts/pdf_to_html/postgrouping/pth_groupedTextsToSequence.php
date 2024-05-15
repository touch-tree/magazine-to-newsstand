<?php
declare(strict_types=1);

/*
    - groups nodes into a natural format
    - creates values for groupSequenceNumber property
    - sorts group-nodes by left ASC, then maxTop ASC
*/

class pth_groupedTextsToSequence
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
                
        for($n=0;$n<$len;$n++)
        {
            $groupBoundary =    digi_pdf_to_html::returnGroupBoundary($assignedGroups[$n]);
            $nodes =            digi_pdf_to_html::returnProperties("groupNumber",$assignedGroups[$n]);
            foreach($nodes as $index => $properties) 
            { 
                $boundary =  digi_pdf_to_html::returnBoundary([$index]);
                $nodes[$index]['maxTop']=$boundary['maxTop'];
            }
            
            //groupSequenceNumber
            uasort($nodes, function ($item1, $item2)  {  if ($item1['left'] == $item2['left']) { return $item1['maxTop'] <=> $item2['maxTop']; }  return $item1['left'] <=> $item2['left'];  });

            $groupSequenceNumber = 0;

            foreach($nodes as $index => $properties) 
            { 
                $groupSequenceNumber += 1;
                $obj['nodes'][$index]['groupSequenceNumber']= $groupSequenceNumber;
               
            }

        }

    }

     //#####################################################################


   
   


}

?>