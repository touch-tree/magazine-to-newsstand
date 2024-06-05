<?php
declare(strict_types=1);

/*
    - groups nodes into a natural format
    - creates values for groupSequenceNumber property
    - sorting from top to bottom
    - sort by top ASC
*/

class pth_groupedNodesTopSequence
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
            $nodes =                digi_pdf_to_html::sortNodesByProperty($nodes,"top");

            $lastFloateNumber=null;
            foreach($nodes as $index => $properties) 
            { 
                if(!sys::isInt($properties['groupSequenceNumber'])) 
                {
                    $lastFloateNumber = $properties['groupSequenceNumber']; 
                }
                elseif(isset($lastFloateNumber))
                {
                    $lastFloateNumber += 0.05;
                    $obj['nodes'][$index]['groupSequenceNumber'] = $lastFloateNumber;
                }
            }
        }
        
    }

     //#####################################################################


   
   


}

?>