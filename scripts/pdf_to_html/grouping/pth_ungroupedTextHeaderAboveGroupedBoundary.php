<?php
declare(strict_types=1);

/*
    - grouo text-nodes with a group-boundary, when the text is atop of the boundary
*/

class pth_ungroupedTextHeaderAboveGroupedBoundary
{    
   
    private $marginY = 30;
    private $marginX = 10;

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
        
        /*
        $keys = array_keys( $obj['nodes'] );
        $len =  sizeof($keys);

        $textNodes = digi_pdf_to_html::returnProperties("tag","text",false);

        $handledGroup=[];
        foreach ($textNodes as $index => $properties) 
        {
           
            $boundary1 = digi_pdf_to_html::returnBoundary([$index]);
            $indx = array_search($index,$keys);
            if(!isset($keys[$indx + 1])) { break; }

            $index2 =       $keys[$indx + 1];
            $propery2=      $obj['nodes'][$index2];

            if($propery2['groupNumber'] == 0 )                  { continue; }
            if(in_array($propery2['groupNumber'],$handledGroup)) { continue; }
            $handledGroup[]=    $propery2['groupNumber'];
            $boundary2 =        digi_pdf_to_html::returnGroupBoundary($propery2['groupNumber']);
            
            
            if( abs($boundary2['top'] - $boundary1['maxTop']) > $this->marginY  ) { continue; }
            if( abs($boundary2['left'] - $boundary1['left']) > $this->marginX  ) { continue; }
          
            $grouped = digi_pdf_to_html::groupNodes([$index,$index2]);
            if($grouped)
            {
                $this->execute($obj);  
                return;
            }

        

        }

        
       */
    }

     //#####################################################################


   
   


}

?>