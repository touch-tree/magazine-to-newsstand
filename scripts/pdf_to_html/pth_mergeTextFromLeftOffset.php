<?php
declare(strict_types=1);

/*
    Group text sections that have a similar left-offset (margin set in $maxcMarginThreshold )
    Grouping is done for texts with the same fontId  
*/

class pth_mergeTextFromLeftOffset
{
    private  $maxTextYSeparator =    8; //max spacing between 2 lines 
    private  $maxcMarginThreshold=   3;

    public function __construct(&$obj)
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
            $indx =  $this->findIndex($arrayLeftCollection,$value);
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
                    if(abs($properties['top'] - ($propertiesPrev['top'] + $propertiesPrev['height']) ) > $this->maxTextYSeparator)                      
                    {
                         continue;  
                    }
                    
                    digi_pdf_to_html::mergeBlocks($obj,$indexPrev,$index,false);     
                }
        }

        $obj['content'] = array_values($obj['content']); //re-index data


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
    //###########################################################
   





}

?>