<?php
declare(strict_types=1);

/*
    - groups nodes into a natural format
    - creates values for groupSequenceNumber property
    - find title but only for left-aligned text-nodes
    - sort by maxTop ASC
*/

class pth_groupedTextsToSequence
{    
   
    private $leftLevel = 10;

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
            $groupId =              $assignedGroups[$n];
            $nodes   =              digi_pdf_to_html::returnProperties("groupNumber",$groupId);
            $leftNodesWithIndexes = digi_pdf_to_html::collectPropertyValues($nodes,"left",$this->leftLevel);

            foreach($leftNodesWithIndexes as $left => $indexes) 
            { 
                if(sizeof($indexes) <= 1) { continue; } 
            }

          
            
        }
        





        /*     
        for($n=0;$n<$len;$n++)
        {
            $groupBoundary =    digi_pdf_to_html::returnGroupBoundary($assignedGroups[$n]);
            $nodes =            digi_pdf_to_html::returnProperties("groupNumber",$assignedGroups[$n]);
            foreach($nodes as $index => $properties) 
            { 
                $boundary =  digi_pdf_to_html::returnBoundary([$index]);
                $nodes[$index]['maxTop']=$boundary['maxTop'];
                $nodes[$index]['center']= round( ($boundary['left'] + $boundary['maxLeft']) / 2 );
            }
            
            //groupSequenceNumber
            uasort($nodes, function ($item1, $item2)  {  if ($item1['center'] == $item2['center']) { return $item1['maxTop'] <=> $item2['maxTop']; }  return $item1['center'] <=> $item2['center'];  });

            $groupSequenceNumber = 0;

            foreach($nodes as $index => $properties) 
            { 
                $groupSequenceNumber += 1;
                $obj['nodes'][$index]['groupSequenceNumber']= $groupSequenceNumber;
               
            }

        }
        */

        //print_r($obj);exit;

    }

     //#####################################################################


   
   


}

?>