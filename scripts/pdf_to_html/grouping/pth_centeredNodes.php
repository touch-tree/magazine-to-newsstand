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

    public function __construct(&$obj)
    {
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);

        //-------------------------------
        $this->execute($obj);       
    }
    
    //#####################################################################

    private function execute(&$obj)
    {
        $textNodes = digi_pdf_to_html::returnProperties($obj,"tag","text");  

        //get centered position
        foreach ($textNodes as $index => $properties) 
        {
                $boundary = digi_pdf_to_html::returnBoundary($obj,[$index]);
                $textNodes[$index]['center'] = round( ($boundary['left'] + $boundary['maxLeft']) / 2);
        }

        $arrayCenterCollection =  digi_pdf_to_html::collectPropertyValues($textNodes,"center",$this->margin);

        foreach ($arrayCenterCollection as $center => $indexes) 
        {
                $len = sizeof($indexes);
                if( $len <= 1 ) { continue; }

                for( $n=0; $n < $len; $n++ )
                {
                    $index=         $indexes[$n];
                    $node =         $obj['content'][$index];
                    $boundary=      digi_pdf_to_html::returnBoundary($obj,[$index]);
                    $index2=        null;
                    $node2=         null;

                    if(isset($indexes[$n+1]))
                    {
                        $index2=        $indexes[$n+1];;
                        $node2=         $obj['content'][$index2];
                        $boundary2=     digi_pdf_to_html::returnBoundary($obj,[$index2]);  
                        
                         //spacing to the next line must be within range/allowence
                        if( ($boundary2['top'] - $boundary['maxTop'] ) > $this->maxTextYSeparator) {continue; }

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


     //------------------------------------------------------------------------

    
   


}

?>