<?php
declare(strict_types=1);
/*
    Text sections sometimes are place in an absolute position within a document. 
    Visually the look however as part of a scentence. These must be merged with the proper block
    Logic based on left-offset values of text blocks.
*/

class pth_floatingTexts
{    
    private $maxcMarginThreshold = 3;
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
            //----------------------------
            //collect indexes based on the left-offset
            $arrayAllSimilarLeftOffsets =   $this->collectAllSimilarLeftOffsets($obj);
            $objGroupedSimilarLeftOffsets = $this->groupAllSimilarLeftOffsets($obj,$arrayAllSimilarLeftOffsets);

            //----------------------------
            //traverse grouped indexes per left position
            $arrayMergers=[];
            foreach( $objGroupedSimilarLeftOffsets as $left => $arrayGroups) 
            {           
                $loop = sizeof($arrayGroups);
                for($n=0;$n<$loop;$n++)
                {
                        $indexes = $arrayGroups[$n];
                        if(sizeof($indexes) <= 1) {continue;}
                        $objBoundary = digi_pdf_to_html::returnBoundary($indexes);
                        $blocksToMove = [];
                        
                        //------------------------------
                        // find elements within the boundary where the following rule applies: left position > $left + margin
                        foreach ($obj['content'] as $index => $properties) 
                        {
                            if(!digi_pdf_to_html::nodeWithinBoundary($properties,$objBoundary,$left))       { continue; }
                            if($properties['left'] <= ($objBoundary['left'] + $this->maxcMarginThreshold))  { continue; } 
                            $blocksToMove[$index]=$properties;
                        }

                        if(sizeof($blocksToMove)==0) { continue;}
                        //----------------------------------
                        //get indexes and the topMax values
                        $arrayMaxTop =      [];
                        $indexesToMove =    array_keys($blocksToMove);
                        foreach ($obj['content'] as $index => $properties) 
                        { 
                            if(in_array($index, $indexesToMove)){ continue; }
                            if(!digi_pdf_to_html::nodeWithinBoundary($properties,$objBoundary,$left))      { continue; }
                            $arrayMaxTop[$index]=$properties['top'] + $properties['height'];
                        }

                        //----------------------------------
                        //append all blocks from $blocksToMove
                        $lastIndex=0;
                        foreach ($blocksToMove as $index => $properties) 
                        {   
                                $keys = array_keys($arrayMaxTop);
                                $loop2 = sizeof($keys);
                                for($i=0;$i<$loop2;$i++)
                                {
                                    $index1 =   $keys[$i];
                                    $top1    =  $arrayMaxTop[$index1];
                                    $index2=    null;
                                    $top2=      null;

                                    if(isset($keys[$i+1]))
                                    {
                                        $index2 =   $keys[$i+1];
                                        $top2    =  $arrayMaxTop[$index2];      
                                    }

                                    $assign=false;
                                    if($properties['top'] > $top1 )
                                    {
                                        if(!isset($top2))
                                        {
                                            $assign=true;
                                            $index2 = $index1;
                                        }
                                        elseif( $properties['top'] < $top2)
                                        {
                                            $assign=true;         
                                        }
                                        
                                        if($assign)
                                        {
                                        $arrayMergers[$index2]=$index;     
                                        }
                                    }

                                }
                        }

                }
            }
        //------------------------------------------
        //merger
        $arrayHandledIndexes=[];
        foreach ($arrayMergers as $src => $tgt) 
        {  
            if( in_array($src,$arrayHandledIndexes) or in_array($tgt,$arrayHandledIndexes) ) { continue;}
            digi_pdf_to_html::mergeNodes($src, $tgt, false);  
            $arrayHandledIndexes[]=$tgt; //target will be removed
        }

        digi_pdf_to_html::reIndex();
        //----------------------------------------
    }


    //###########################################################
    //Group all identical 'left' property values (for text-nodes)  and gather the index values
    //!! note: the returned key-values should not be used for any calculation(!) because the key-values are estimats based on $this->findIndex()
    private function collectAllSimilarLeftOffsets(&$obj):array
    {
        $arrayLeftCollection = [];

        foreach ($obj['content'] as $index => $item) 
        {
            if($item['tag'] === "image") { continue; }
            $value = $item["left"];
            $indx =  $this->findIndex($arrayLeftCollection,$value);
            if(!isset($indx))
            {
                $arrayLeftCollection[$value]=[$index];
            }
            else 
            {
                $arrayLeftCollection[$indx][] = $index;
            }
        }

        return  $arrayLeftCollection;
    }

    //###########################################################
    //group $arrayAllSimilarLeftOffsets into blocks. Section of a ppage may have the same left-value even though it is a different section
    //!! note: the returned key-values should not be used for any calculation(!) because the key-values are estimats based on $this->findIndex()
    private function groupAllSimilarLeftOffsets(&$obj, array $arrayAllSimilarLeftOffsets):array
    {
        $arrayBlocks =  [];
        foreach ( $arrayAllSimilarLeftOffsets as $left => $indexes ) 
        {
            $arrayBlocks[$left]=[];
            $len = sizeof( $indexes );
            $setIndex=0;
            $arrayBlocks[$left][$setIndex] = [];

            if($len <= 1) { $arrayBlocks[$left][$setIndex] = $indexes; }
            else
            {
                
                for( $n=0; $n<$len; $n++ )
                {   
                        $index =        $indexes[$n];
                        $properties =   $obj['content'][$index];
                        $nextN=         $n+1;
                        array_push($arrayBlocks[$left][$setIndex],$index);
        
                        if(isset($indexes[$nextN]))
                        {
                            $indexNext=         $indexes[$nextN];
                            $propertiesNext=    $obj['content'][$indexNext];    

                            //next line spacing must be within range/allowence
                            $diff = abs(  $propertiesNext['top'] - ($properties['top'] + $properties['height']) );                           
                            if( $diff > $this->maxTextYSeparator )                      
                            {
                                $setIndex += 1;
                                $arrayBlocks[$left][$setIndex]=[]; 
                            }  
                        }
                }


            }
        }

        return $arrayBlocks;

    }
    //###########################################################
    private function findIndex(array $array, int $val):?int
    {
        $min = $val - $this->maxcMarginThreshold;
        $max = $val + $this->maxcMarginThreshold;
        for($n = $min; $n<=$max;$n++)
        {
            if(isset($array[$n])) { return $n;}
        }

        return null;
    }
    //####################################################################


}

?>