<?php
declare(strict_types=1);
//#################################################################################
class pth_mergeTextFromCentered
{
    
    private  $maxTextYSeparator =            8; //max spacing between 2 lines 
    private  $maxHeightThreshold=            60; 
    private  $maxcenterMarginThreshold=      6;

    public function __construct(&$obj)
    {
        
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
        $arrayBlocks = [];

        //only collect relevant text-nodes
        $len = sizeof( $obj['content'] );  
        for( $n = 0; $n < $len; $n++ )
        {
           if( $obj['content'][$n]['tag'] !== "text")                       { continue; }   //ignore images
           $arrayBlocks[$n] = $obj['content'][$n];
        }

        
        //--------------------
        //calculate center position of texts, and link these with main indexes of the main data object $obj
        $arrayCentered = [];

        foreach ($arrayBlocks as $index => $properties) 
        {
                $centerValue = sys::posInt(ceil(($properties['left'] + ($properties['left'] + $properties['width'])) / 2));
                $centerIndx =  $this->findIndex($arrayCentered,$centerValue);
                if(!isset($centerIndx)){
                    $arrayCentered[$centerValue]=[$index];
                }
                else {
                    $arrayCentered[$centerIndx][] = $index;
                }
        }
        //--------------------

        foreach ($arrayCentered as $leftVal => $indexes) 
        {
            $len =  sizeof($indexes);
            if( $len <= 1 ) { continue;}

            for($n=($len-1);$n>=1;$n--)
            {
                $index=         $indexes[$n];
                $properties =   $obj['content'][$index];

                $indexPrev=         $indexes[$n-1];
                $propertiesPrev =   $obj['content'][$indexPrev];

                //make surefont definition is the same
                if($properties['fontId'] <> $propertiesPrev['fontId'] )                                    
                {
                     continue; 
                }

                //next line spacing must be within range/allowence
                if(abs($properties['top'] - ($propertiesPrev['top'] + $propertiesPrev['height']) ) > $this->maxTextYSeparator)                      
                {
                     continue;  
                }

                
                digi_pdf_to_html::mergeBlocks($obj,$indexPrev,$index,false);     
            }

        }

        $obj['content'] = array_values($obj['content']); //re-index data   





    }

    //##############################################################################
    private function findIndex(array $array, int $centerValue):?int
    {
        $min = $centerValue - $this->maxcenterMarginThreshold;
        $max = $centerValue + $this->maxcenterMarginThreshold;
        for($n = $min; $n<=$max;$n++)
        {
            if(isset($array[$n])) { return $n;}
        }

        return null;
    }
    //############################################################################

}

?>