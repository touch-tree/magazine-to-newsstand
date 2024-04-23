<?php

class pdf_to_html_text_centered_merging
{

    static private  $maxTextYSeparator =            8; //max spacing between 2 lines 
    static private  $maxHeightThreshold=            60; 
    static private  $maxcenterMarginThreshold=      4;
    //#####################################################################
    static private function findIndex(array $array, int $centerValue):?int
    {
        $min = $centerValue - self::$maxcenterMarginThreshold;
        $max = $centerValue + self::$maxcenterMarginThreshold;
        for($n = $min; $n<=$max;$n++)
        {
            if(isset($array[$n])) { return $n;}
        }

        return null;
    }
    //#####################################################################

    static public function process(&$obj):void
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
           if( $obj['content'][$n]['height'] > self::$maxHeightThreshold )  { continue; }   //previously merged texts (thus gathered a certain height), but not assigned to a group yet.
           $arrayBlocks[$n] = $obj['content'][$n];
        }

        //--------------------
        //calculate center position of texts, and link these with main indexes of the main data object $obj
        $arrayCentered = [];

        foreach ($arrayBlocks as $index => $properties) 
        {
                $centerValue = ceil(($properties['left'] + ($properties['left'] + $properties['width'])) / 2);
                $centerIndx =  self::findIndex($arrayCentered,$centerValue);
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
                if(abs($properties['top'] - ($propertiesPrev['top'] + $propertiesPrev['height']) ) > self::$maxTextYSeparator)                      
                {
                     continue;  
                }

                digi_pdf_to_html::mergeBlocks($obj,$indexPrev,$index,false);     
            }

        }

    }
    //#####################################################################

}

?>