<?php
declare(strict_types=1);
/*
    - merges text columns
    - must have same font-size
    - a max seperation between them ($maxTextColumnSeparator)
    - same top offset (with margin $margin )

     ##########################################################################################
    SCENARIO

        ---- ----       _____________
        -- ------       |           |
                        |           |
        #title          |   IMG     |
                        |           |
        ---- ----       |           |
        --- -----       _____________
        --- -----
        ----- ---       --- --- -----
        ---- ----       -- - --- ----

*/


class pth_textColumnsPostBlockWithImage
{    
    private  $maxTextColumnSeparator =  30; 
    private  $marginRight = 8; 
    private  $marginLeft =  3; 
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
        $nodes = $obj['nodes'];
        foreach ($nodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            $nodes[$index]['maxLeft'] = $boundary['maxLeft'];      
        }

     
        $arrayRightCollection =    digi_pdf_to_html::collectPropertyValues($nodes,"maxLeft",$this->marginRight);
        $arrayLeftCollection =     digi_pdf_to_html::collectPropertyValues($nodes,"left",$this->marginLeft);

        //group the right-aligned blocks that belong together, based on a max seperation between them (given in $maxBlockYSeparator)
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
                    if($maxTopDiff <= $this->maxBlockYSeparator )
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


                $lastMaxTop = $boundary['maxTop'];
            }
            
        }


        //traverse the found sections
        foreach ($arrayRightSections as $maxLeft => $sections) 
        {
                
                $loop = sizeof($sections);
                for($n=0;$n<$loop;$n++)
                {
                            $indexes = $sections[$n];
                            foreach ($arrayLeftCollection as $left => $indexes2) 
                            {
                                    if($left <= $maxLeft)       { continue; }
                                    if(sizeof($indexes2) <= 1)  { continue; }
                    
                                    $firstIndex =   $indexes2[0];
                                    $secondIndex =  $indexes2[1];
                    
                                    if($obj['nodes'][$firstIndex]['tag'] !== "image") { continue; }
                                    if($obj['nodes'][$secondIndex]['tag'] !== "text") { continue; }
                    
                                    $diff = abs($maxLeft - $left);
                                    if($diff > $this->maxTextColumnSeparator ) { continue;}
                    
                                    $lastIndex = end($indexes);

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


                                    $node1 = $obj['nodes'][$lastIndex]; 
                                    $node2 = $obj['nodes'][$secondIndex];
                        
                                    if(!digi_pdf_to_html::textNodesAreMergable($node1,$node2) ) { continue ; }
                                    digi_pdf_to_html::mergeNodes($lastIndex,$secondIndex); 
                                    $this->execute($obj);
                                    return;
                
                            }

                }

        }


 
  

    }
    //############################################################
    
    
   


}

?>