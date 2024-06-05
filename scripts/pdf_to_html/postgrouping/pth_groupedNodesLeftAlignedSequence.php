<?php
declare(strict_types=1);

/*
    - groups nodes into a natural format
    - creates values for groupSequenceNumber property
    - find title but only for left-aligned text-nodes
    - sort by maxTop ASC
*/

class pth_groupedNodesLeftAlignedSequence
{    
   
    private $leftLevel = 5;

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
                $leftNodes = digi_pdf_to_html::returnNodesFromIndexes($indexes);
                foreach($leftNodes as $index => $properties) 
                { 
                    $boundary = digi_pdf_to_html::returnBoundary([$index]);
                    $leftNodes[$index]['maxTop'] = $boundary['maxTop'];
                }

                //SORT nodes by maxTop ASC.....
                $leftNodes = digi_pdf_to_html::sortNodesByProperty($leftNodes,"maxTop");

                $offsetSeqenceNumber = reset($leftNodes)['groupSequenceNumber'];
                $counter = 0;
                foreach($leftNodes as $index => $properties) 
                { 
                    $counter += 0.1;
                    $obj['nodes'][$index]['groupSequenceNumber'] = $offsetSeqenceNumber + $counter;
                }
         
            }   
        }
        






    }

     //#####################################################################


   
   


}

?>