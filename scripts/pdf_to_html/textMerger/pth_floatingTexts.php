<?php
declare(strict_types=1);
/*
    Text sections sometimes are place in an absolute position within a document. 
    Visually the look however as part of a scentence. These must be merged with the proper block
    Logic based on left-offset values of text blocks.
*/

class pth_floatingTexts
{    
    private $maxMarginThreshold =       3;
    private $maxTextYSeparator =        8;
    private $maxMarginXAppender =       25;
    private $boundaryMargin =           2;
    private $sourceLineMarginLeft =     8;
    
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
        $arrayLeftCollection = $this->collectAllSimilarLeftOffsets();
        $arrayLeftCollection = $this->groupAllSimilarLeftOffsets($obj,$arrayLeftCollection);
        $textNodes =            digi_pdf_to_html::returnProperties("tag","text",false); 
        $textNodes =            digi_pdf_to_html::sortNodesByProperty($textNodes,"left");
    
        foreach( $arrayLeftCollection as $left => $groups) 
        { 
                foreach( $groups as $n => $indexes) 
                { 
                    $boundaryBlock = digi_pdf_to_html::returnBoundary($indexes);
                    $boundaryBlock['top'] -=        $this-> boundaryMargin;
                    $boundaryBlock['maxLeft'] +=    $this-> sourceLineMarginLeft;
                    $boundaryBlock['maxTop'] +=     $this-> boundaryMargin;
                    $boundaryBlock['left'] -=       $this-> boundaryMargin;

                    foreach( $textNodes as $index=> $properties) 
                    { 

                        if(!digi_pdf_to_html::nodeWithinBoundary($properties,$boundaryBlock))              { continue; }   // the floating node must be within the boundary    
                        if($properties['left'] <= ($boundaryBlock['left'] + $this->maxMarginThreshold))    { continue; }   // the floating node seems too much to the left. It is expected that the text must be appended at the end of the source node.
                        $propertyBoundary = digi_pdf_to_html::returnBoundary([$index]);

                        foreach( $indexes as $i => $subIndex) 
                        {
                            if($subIndex == $index)                                                         { continue; }
                            $boundarynode = digi_pdf_to_html::returnBoundary([$subIndex]);
                            $boundarynode['maxLeft'] = $boundaryBlock['maxLeft'];

                            //get center x and y of node as a small block to ensure there are no overlaps
                            $centeredProp = $properties;
                            $centeredProp['top'] =      sys::posInt(round(($propertyBoundary['top'] + $propertyBoundary['maxTop']) / 2));
                            $centeredProp['left'] =     sys::posInt(round(($propertyBoundary['left'] + $propertyBoundary['maxLeft']) / 2));
                            $centeredProp['width'] =    3;
                            $centeredProp['height'] =   3;
           
                            if(!digi_pdf_to_html::nodeOverlapsBoundary($centeredProp,$boundarynode))          { continue; }  
                            
                            digi_pdf_to_html::mergeNodes($subIndex,$index); 
                            $this->execute($obj);
                            return;
                        }
                    }
                }
        }
    }
    
    //########################################################################################################
    //########################################################################################################
    //########################################################################################################
    //HELPER FUNCTIONS
    //########################################################################################################
    //########################################################################################################
    //########################################################################################################

    //Group all identical 'left' property values (for text-nodes)  and gather their index values
    //!! note: the returned key-values should not be used for any calculation(!) because the key-values are estimates based on $this->findIndex()
    
    private function collectAllSimilarLeftOffsets():array
    {
        $arrayLeftCollection = [];
        $textNodes =  digi_pdf_to_html::returnProperties("tag","text",false); 
        foreach ($textNodes as $index => $item) 
        {
            $value = $item["left"];
            $indx =  $this->findIndex($arrayLeftCollection,$value);
            if(!isset($indx)) { $arrayLeftCollection[$value]=[$index]; } else  { $arrayLeftCollection[$indx][] = $index; }
        }

        return  $arrayLeftCollection;
    }

    //---------------

    private function findIndex(array $array, int $val):?int
    {
        $min = $val - $this->maxMarginThreshold;
        $max = $val + $this->maxMarginThreshold;
        for($n = $min; $n<=$max;$n++)
        {
            if(isset($array[$n])) { return $n;}
        }

        return null;
    }

    //--------------
    /*
        group $arrayAllSimilarLeftOffsets into blocks. Section of a ppage may have the same left-value even though it is a different sectiona based on:
            - vertical spacing, set in $this->maxTextYSeparator 
            - different fontSize

        note: the returned key-values should not be used for any calculation(!) because the key-values are estimats based on $this->findIndex()
    */
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
                        $properties =   $obj['nodes'][$index];
                        $nextN=         $n+1;
                        array_push($arrayBlocks[$left][$setIndex],$index);
        
                        if(isset($indexes[$nextN]))
                        {
                            $indexNext=         $indexes[$nextN];
                            $propertiesNext=    $obj['nodes'][$indexNext];    

                            //next line spacing must be within range/allowence
                            $diff = abs(  $propertiesNext['top'] - ($properties['top'] + $properties['height']) ); 
                            $createNewGroup = false;
                            if( $diff > $this->maxTextYSeparator )                          { $createNewGroup = true;  }                        
                            if( $properties['fontSize'] <> $propertiesNext['fontSize'] )    { $createNewGroup = true;  }  

                            if( $createNewGroup )                      
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

    //########################################################################################################
    //########################################################################################################
    //########################################################################################################
    //END OF THE HELPER FUNCTIONS
    //########################################################################################################
    //########################################################################################################
    //########################################################################################################


}

?>