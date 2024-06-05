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
        $textNodes =              digi_pdf_to_html::returnProperties("tag","text",false);    
        $arrayLeftCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"left",$this->margin);




        foreach ($arrayLeftCollection as $leftVal => $indexes) 
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

  

                     //---------------------------------------------
                     //substitute a new-line for a white-space
                     if
                     ( 
                        $boundary2['top'] > $boundary['top']  
                        &&  ($boundary2['top'] - $boundary['maxTop'] ) <= $this->maxTextYSeparator
                        &&  !digi_pdf_to_html::textNodesAreMergable($node,$node2) 
                     ) 
                     {
                         $lastChar = sys::substr(strip_tags($node['content']), -1);  
                         $newChar  = sys::substr(strip_tags($node2['content']),0,1); 

                         if( (sys::isAlpha($lastChar)) && sys::isAlpha($newChar) || in_array($newChar,["+","&"]) )
                         {
                             $node2['content'] = " ".$node2['content'];
                             if(digi_pdf_to_html::textNodesAreMergable($node,$node2))
                             {
                                $obj['nodes'][$index2]['content'] = $node2['content']; 
                             }   
                         } 
                     }
                     //---------------------------------------------

    
                     if(!digi_pdf_to_html::textNodesAreMergable($node,$node2) )              { continue ; }

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