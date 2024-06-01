<?php
declare(strict_types=1);
/*
    - merges text columns
    - must have same font-size
    - a max seperation between them ($maxTextColumnSeparator)
    - same top offset (with margin $margin )

     ##########################################################################################
     SCENARIO 
     3 nodes , with the same top-value, and a maximum distance inbetween them. In this case node 2 (bottom block) and node 3 will be merged.

        -----------------       --------------
        -----------------       ---------------
        -----------------       ---------
        -----------------       ------------
        ----------
        -----------------       
        ----------

        #### new title ###

        -----------------
        -------
        -----------------
        ------
        -----------------

   
*/


class pth_textColumnsPreBlockWithTitle
{    
    private  $maxTextColumnSeparator =  30; 
    private  $marginRight = 15; 
    private  $marginTop =   2; //margin for top-value; 
    private  $maxBlockYSeparator =      100; 

    
    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();

        //-------------------------------
        $this->execute($obj);       
    }
    
    //#####################################################################
    private function execute(&$obj):void
    {

        $textNodes =  digi_pdf_to_html::returnProperties("tag","text",false);  

        
        
        //--------------------------------------------
        //get maxLeft position
        foreach ($textNodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            $textNodes[$index]['maxLeft'] = $boundary['maxLeft'];
        }

        $arrayRightCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"maxLeft",$this->marginRight);

      
        //group the right-aligned blocks that belong together, based on:
        //- max Y seperation between them (given in $maxBlockYSeparator)
   

        $arrayRightSections=[];
        foreach ($arrayRightCollection as $maxLeft => $indexes) 
        {
            if(!isset($arrayRightSections[$maxLeft])) {$arrayRightSections[$maxLeft]=[];}
            $len =          sizeof($indexes);
            $groupIndex=    0;
            $lastMaxTop=    null;
         
            for($n=0;$n<$len;$n++)
            {
                $index = $indexes[$n];
                $boundary = digi_pdf_to_html::returnBoundary([$index]);
                if(!isset($arrayRightSections[$maxLeft][$groupIndex]))
                {
                    $arrayRightSections[$maxLeft][$groupIndex]=[];   
                }

                if(!isset($lastMaxTop)) { $arrayRightSections[$maxLeft][$groupIndex][] = $index; }
                else
                {
                    $maxTopDiff = abs($lastMaxTop - $boundary['top']);
                 
                    if($maxTopDiff <= $this->maxBlockYSeparator)
                    {
                        $arrayRightSections[$maxLeft][$groupIndex][] = $index;     
                    }
                    else
                    {
                        $groupIndex += 1;   
                        $arrayRightSections[$maxLeft][$groupIndex]=[];  
                        $arrayRightSections[$maxLeft][$groupIndex][] = $index;  
                    }
                }


                $lastMaxTop =   $boundary['maxTop'];
                $lastLeft =     $boundary['left'];
            }
            
        }



        //traverse the found sections
        foreach ($arrayRightSections as $maxLeft => $sections) 
        {
               
                $loop = sizeof($sections);
                for($n=0;$n<$loop;$n++)
                {
                            $indexes =      $sections[$n];
                            $minTop =       digi_pdf_to_html::returnMinMaxProperyValue("top",$indexes,false);
                            $lastIndex =    end($indexes);

                    

                           
                            //check if there is another node embedded within a larger one; if so the latest/lowest one applies
                            $embeddedIndexes =  $arrayRightCollection[$maxLeft];
                            $len =              sizeof($embeddedIndexes);
                            $boundary =         digi_pdf_to_html::returnBoundary([$lastIndex]); 
                            for($i=0;$i<$len;$i++)
                            {
                                $embeddedBoundary = digi_pdf_to_html::returnBoundary([$embeddedIndexes[$i]]); 
                                if($embeddedBoundary['top']<=$boundary['top'])    {continue;}
                                if(digi_pdf_to_html::nodeOverlapsBoundary($embeddedBoundary,$boundary))
                                {
                                    $lastIndex = $embeddedIndexes[$i];
                                    $boundary = digi_pdf_to_html::returnBoundary([$lastIndex]);
                                }  
                            }

       

                            //loop all nodes
                            foreach ($textNodes as $index => $properties) 
                            {
                       
                                
                                $boundary = digi_pdf_to_html::returnBoundary([$index]);
                                if($boundary['left'] <= $maxLeft)                                      { continue; } //the next column must have a larger left-position
                                if( abs($boundary['top'] - $minTop) > $this->marginTop)                { continue; } //top top-position does not match 
                                if( abs($boundary['left'] - $maxLeft) > $this->maxTextColumnSeparator) { continue; } //spacing to the next line must be within range/allowence

                                if(!digi_pdf_to_html::textNodesAreMergable($obj['nodes'][$lastIndex],$properties) ) { continue ; }
                                digi_pdf_to_html::mergeNodes($lastIndex,$index); 
                                $this->execute($obj);
                                return;
                            }

                }


        }

   

    }
    //#####################################################################
    


}

?>