<?php
declare(strict_types=1);

/*
    - Merge text sections that have a similar left-offset (margin set in $margin )
    - Merging is done for texts with the same fontSize  
    - Nodes may not be grouped yet (argument set in returnProperties() ... )
    - for example:

        section below would result in 4 mergers:

        --------------------
        -----------
        ----------------
        --------------------


        -------------------
        --------------
        -------------------
        -------------------------------


        -----------------       --------------
        -----------------       ---------------
        -----------------       ---------
        -----------------       ------------
        ----------
        -----------------       IMG
        ----------

        the bottom example is a column layout; each column section will be merged
*/

class pth_leftAlignedTexts
{    
    private $margin = 3;
    private $maxTextYSeparator =   8;
    private $arrayLeftCollection = [];
    
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
        $this->arrayLeftCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"left",$this->margin);

        foreach ($this->arrayLeftCollection as $leftVal => $indexes) 
        {
                $len = sizeof($indexes);
                if( $len <= 1 ) { continue; }

                for( $n=0; $n < $len; $n++ )
                {



                    if(isset($indexes[$n+1]))
                    {
                        
                        $index=         $indexes[$n];
                        $node =         $obj['content'][$index];
                        $boundary=      digi_pdf_to_html::returnBoundary([$index]);
                        
                        $index2=        $indexes[$n+1];;
                        $node2=         $obj['content'][$index2];
                        $boundary2=     digi_pdf_to_html::returnBoundary([$index2]);  
                        
                        //make sure font-size is the same
                        if( $node['fontSize'] <> $node2['fontSize'] ) { continue; }

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