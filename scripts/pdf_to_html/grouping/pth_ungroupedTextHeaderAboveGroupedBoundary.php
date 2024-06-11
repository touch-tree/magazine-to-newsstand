<?php
declare(strict_types=1);

/*
    - grouo text-nodes with a group-boundary, when the text is atop of the boundary
*/

class pth_ungroupedTextHeaderAboveGroupedBoundary
{    
   
    private $marginY =              25;
    private $marginX =              10;
    /* private $maxHeaderCharsLen =    30; */

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
        $textNodes =         digi_pdf_to_html::returnProperties("tag","text",false);

        //sort textNodes from Top DESC (depending how large marginY is, it may else allow anther node in between)
        $textNodes = digi_pdf_to_html::sortNodesByProperty($textNodes,"top",false);
                
        for($n=0;$n<$len;$n++)
        {
            $boundary = digi_pdf_to_html::returnGroupBoundary($assignedGroups[$n]);
            
            foreach ($textNodes as $index => $properties) 
            {
                $boundary2 = digi_pdf_to_html::returnBoundary([$index]);
                if( abs($boundary['top'] - $boundary2['maxTop']) > $this->marginY  )    { continue; }
                if( abs($boundary['left'] - $boundary2['left']) > $this->marginX  )     { continue; }

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