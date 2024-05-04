<?php
declare(strict_types=1);

/*
    - Merge nodes that have a similar left-offset (margin set in $margin ) 
    - Nodes may be grouped already, and ungrouped nodes can then be assigned to the available group
    - for example:

        section below would result in 4 nodes being grouped into 2 groups:

        ### title ###
        ----------------------
        ----------
        ------------------
        ----------------------
        ----- ----------------

        


        ### title2 ###
        ----------------------
        ----------
        ------------------
        ----------------------
        ----- ----------------



*/

class pth_leftAlignedNodes
{    
    private $margin = 3;
    private $maxTextYSeparator =   8;


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
        $textNodes =                    digi_pdf_to_html::returnProperties("tag","text");    
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