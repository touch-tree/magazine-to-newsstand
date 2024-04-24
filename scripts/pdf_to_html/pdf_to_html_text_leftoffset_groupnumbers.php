<?php

class pdf_to_html_text_leftoffset_groupnumbers
{

    static private  $maxBlockYSeparator =  8; //max spacing between 2 sections 
    static private  $maxcMarginThreshold=  3;
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
        
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);


        //-----------------------------------------------
        //group all identical 'left' property values (for both image and text-nodes) together and gather the relatied index values from the base-data object
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
                    $index=             $indexes[$n];
                    $properties =       $obj['content'][$index];
                    $groupId1 =         $properties['groupNumber'];

                    $indexPrev=         $indexes[$n-1];
                    $propertiesPrev =   $obj['content'][$indexPrev];
                    $groupId2 =         $propertiesPrev['groupNumber'];

                    //,ax spacing between sections
                    if(abs($properties['top'] - ($propertiesPrev['top'] + $propertiesPrev['height']) ) > self::$maxBlockYSeparator)                      
                    {
                         continue;  
                    }

                    //both texts sections are already grouped somewhere else
                    if($groupId1 > 0 && $groupId2 > 0)
                    {
                        continue;
                    }

                    if($groupId1 > 0)        {$groupId =  $groupId1;}
                    elseif($groupId2 > 0)    {$groupId =  $groupId2;}
                    else                     {$groupId =  digi_pdf_to_html::getNewGroupNumber($obj);}

                    $obj['content'][$index]['groupNumber'] = $groupId;
                    $obj['content'][$indexPrev]['groupNumber'] = $groupId;

                       
                }
        }

        


    }
    //#####################################################################

}

?>