<?php
declare(strict_types=1);

/*

   - Merge nodes that have a similar center-offset (margin set in $margin ) 
   - Nodes may be grouped already, and ungrouped nodes can then be assigned to the available group

*/

class pth_centeredNodes
{    

    private  $maxTextYSeparator =            8; //max spacing between 2 lines 
    private  $margin=                        6; //deviation margin from the center

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
        $textNodes = digi_pdf_to_html::returnProperties("tag","text");  

        //get centered position
        foreach ($textNodes as $index => $properties) 
        {
                $boundary = digi_pdf_to_html::returnBoundary([$index]);
                $textNodes[$index]['center'] = round( ($boundary['left'] + $boundary['maxLeft']) / 2);
        }

        $arrayCenterCollection =  digi_pdf_to_html::collectPropertyValues($textNodes,"center",$this->margin);

        foreach ($arrayCenterCollection as $center => $indexes) 
        {
                $len = sizeof($indexes);
                if( $len <= 1 ) { continue; }

                for( $n=0; $n < $len; $n++ )
                {
                    if(isset($indexes[$n+1]))
                    {
                        
                        $index=         $indexes[$n];
                        $node =         $obj['nodes'][$index];
                        $boundary=      digi_pdf_to_html::returnBoundary([$index]);
                           
                        $index2=        $indexes[$n+1];;
                        $node2=         $obj['nodes'][$index2];
                        $boundary2=     digi_pdf_to_html::returnBoundary([$index2]);  
                        
                         //spacing to the next line must be within range/allowence
                        if( ($boundary2['top'] - $boundary['maxTop'] ) > $this->maxTextYSeparator) {continue; }

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


     //------------------------------------------------------------------------

    
   


}

?>