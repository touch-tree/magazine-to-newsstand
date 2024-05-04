<?php
declare(strict_types=1);

/*
    - Merge text sections that have a similar right-offset (margin set in $margin )
    - Merging is done for texts with the same fontSize  
    - Nodes may not be grouped yet (argument set in returnProperties() ... )
    - must come after left-alignment code. The left-alignment created new text-blocks that may have matching right alignments. 
    - For example: several nodes were (previously) merged into 2 nodes (because of placement image). These will now be nerged into 1.


                --------
           IMG  ------------ *
                ----------  
                ------------ *
        -------------------
        -------------------- *
        ------------------
        ----------
        ------------------

        * matches found


*/

class pth_rightAlignedTexts
{    
    private $margin =               8;
    private $maxTextYSeparator =    8;
    private $arrayRightCollection = [];
    
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
        
        //get maxLeft position
        foreach ($textNodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            $textNodes[$index]['maxLeft'] = $boundary['maxLeft'];
        }

 
        $this->arrayRightCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"maxLeft",$this->margin);

        foreach ($this->arrayRightCollection as $maxLeft => $indexes) 
        {
                $len = sizeof($indexes);
                if( $len <= 1 ) { continue; }

                for( $n=0; $n < $len; $n++ )
                {
                    $index=         $indexes[$n];
                    $node =         $obj['content'][$index];
                    $boundary=      digi_pdf_to_html::returnBoundary([$index]);
                    $index2=        null;
                    $node2=         null;

                    if(isset($indexes[$n+1]))
                    {
                        $index2=        $indexes[$n+1];;
                        $node2=         $obj['content'][$index2];
                        $boundary2=     digi_pdf_to_html::returnBoundary([$index2]);  
                        
                        //make sure font-size is the same
                        if( $node['fontSize'] <> $node2['fontSize'] ) { continue; }

                         //spacing to the next line must be within range/allowence
                        if( abs($boundary2['top'] - $boundary['maxTop'] ) > $this->maxTextYSeparator) {continue; }

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