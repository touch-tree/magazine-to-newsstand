<?php

class pdf_to_html_text_leftoffset_merging
{

    static private  $maxTextYSeparator =    8; //max spacing between 2 lines 
    static private  $maxcMarginThreshold=   3;
    //#####################################################################

    static private function findIndex(array $array, int $val):?int
    {
        $min = $val - self::$maxcMarginThreshold;
        $max = $val + self::$maxcMarginThreshold;
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
        
        //-----------------------------------------------
        //group all identical 'left' property values (for text-nodes) together and gather the relatied index values from the base-data object
        //note: the left-value can be used as indexing value in $arrayLeftCollection, and will not be used for actual positioning or calculations later on.
        $arrayLeftCollection = [];

        foreach ($obj['content'] as $index => $item) 
        {
            if($item['tag'] === "image") { continue; }
            $value = $item["left"];
            $indx =  self::findIndex($arrayLeftCollection,$value);
            if(!isset($indx)){
                $arrayLeftCollection[$value]=[$index];
            }
            else {
                $arrayLeftCollection[$indx][] = $index;
            }
        }

        //-------------------------------------------------
        //loop found collection
        foreach ($arrayLeftCollection as $leftVal => $indexes) 
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

        $obj['content'] = array_values($obj['content']); //re-index data
 
    }
    //#####################################################################

}

?>