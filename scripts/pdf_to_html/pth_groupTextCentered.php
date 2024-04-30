<?php
declare(strict_types=1);
//########################################################

class pth_groupTextCentered
{
    private  $maxTextYSeparator =            8; //max spacing between 2 sections 
    private  $maxcenterMarginThreshold=      6;

    public function __construct(&$obj)
    {
        //-----------------------------------------------  //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);

        //-----------------------------------------------
        //calculate center position of texts, and link these with main indexes of the main data object $obj
        $arrayCentered =    [];
        foreach ($obj['content'] as $index => $properties) 
        {
                if($properties['tag'] === "image") { continue; }
                $centerValue = sys::posInt(ceil(($properties['left'] + ($properties['left'] + $properties['width'])) / 2));
                $centerIndx =  $this->findIndex($arrayCentered,$centerValue);

                if(!isset($centerIndx)){
                    $arrayCentered[$centerValue]=[$index];
                }
                else {
                    $arrayCentered[$centerIndx][] = $index;
                }
        }
        //-----------------------------------------------
        //gather index to group together
        foreach ($arrayCentered as $left => $indexes) 
        {
            $len = sizeof($indexes);
            if($len <= 1) {continue;}
            $clone = digi_pdf_to_html::filterSelectedIndexes($obj, $indexes); 

            $lastProp = null;
            $arrayIndexToGroup=[];

            foreach ($clone as $index => $properties) 
            {
                //next line spacing must be within range/allowence
                if($lastProp)
                {
                    if(abs($properties['top'] - ($lastProp['top'] + $lastProp['height']) ) > $this->maxTextYSeparator)                      
                    {
                        break;  
                    }
                }
 
                $arrayIndexToGroup[]=$index;
                $lastProp = $properties;
            }


            $len = sizeof($arrayIndexToGroup);
            if($len > 1)
            {
                for($n=($len-1);$n>=1;$n--)
                {

                    $index=         $indexes[$n];
                    $properties =   $obj['content'][$index];

                    $indexPrev=         $indexes[$n-1];
                    $propertiesPrev =   $obj['content'][$indexPrev];

                    $groupId1 = $properties['groupNumber'];  
                    $groupId2 = $propertiesPrev['groupNumber'];  
                    if($groupId1 > 0 and $groupId2>0) {continue;}

                    if($groupId1 > 0)        {$groupId =  $groupId1;}
                    elseif($groupId2 > 0)    {$groupId =  $groupId2;}
                    else                     {$groupId =  digi_pdf_to_html::getNewGroupNumber($obj);}

                    $obj['content'][$index]['groupNumber'] =        $groupId;
                    $obj['content'][$indexPrev]['groupNumber'] =    $groupId;
                } 

            
            
            } 
        }

    }

    //###########################################################
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
    //###########################################################


}

?>