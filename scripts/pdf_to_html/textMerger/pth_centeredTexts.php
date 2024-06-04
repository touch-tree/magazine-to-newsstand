<?php
declare(strict_types=1);

/*
    - Merge text sections that have a similar center-value (margin set in $margin )
    - Merging is done for texts with the same fontSize  
    - Nodes may not be grouped yet (argument set in returnProperties() ... )

    - For example: several nodes will now be nerged into 2 nodes.

        ---------- | ---------
            -----  | -------
          -------  | --------  
   

        -------------------- | --------------------- 
            ---------------- | ----------------
                  ---------- | ---------
                      -----  | -------
                    -------  | --------  


*/

class pth_centeredTexts
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
        $textNodes =                    digi_pdf_to_html::returnProperties("tag","text",false);  

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

                        if($node['fontId'] <> $node2['fontId']) { continue; }
                        
                        if(!digi_pdf_to_html::textNodesAreMergable($node,$node2) ) { continue ; }

                         //spacing to the next line must be within range/allowence
                        if( ($boundary2['top'] - $boundary['maxTop'] ) > $this->maxTextYSeparator) {continue; }

                        digi_pdf_to_html::mergeNodes($index,$index2); 
                        $this->execute($obj);
                        return;
                    }
                }
        }


        
  
        
        
       
    }

     //#####################################################################


     //------------------------------------------------------------------------

    
   


}

?>