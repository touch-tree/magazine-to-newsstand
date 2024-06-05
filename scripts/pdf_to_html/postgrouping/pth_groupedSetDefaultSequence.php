<?php
declare(strict_types=1);

/*
    - groups nodes into a natural format
    - creates values for groupSequenceNumber property
*/

class pth_groupedSetDefaultSequence
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

        //------------------------
        //default ordering WITHIN groups
        for($n=0;$n<$len;$n++)
        {
            $nodes =                digi_pdf_to_html::returnProperties("groupNumber",$assignedGroups[$n]);
            $groupSequenceNumber =  0;
            foreach($nodes as $index => $properties) 
            {
                $groupSequenceNumber += 1;
                $obj['nodes'][$index]['groupSequenceNumber']= $groupSequenceNumber;
            }
        }

        //------------------------
    }

     //#####################################################################


   
   


}

?>