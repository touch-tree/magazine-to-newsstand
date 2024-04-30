<?php
declare(strict_types=1);

/*
    Group text sections that have a similar right-offset (margin set in $maxMarginThreshold )
    Grouping is done for texts with the same fontId  
*/

class pth_mergeTextFromRightOffset
{
    private  $maxTextYSeparator =    8; //max spacing between 2 lines 
    private  $maxMarginThreshold=    3;

    public function __construct(&$obj)
    {
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj); 

    
        //------------------------------------------------------
        $arrayRightCollection = [];

        foreach ($obj['content'] as $index => $properties) 
        {
            if( $properties['tag'] !== "text" ) { continue; }
            
            $value = $properties['left'] + $properties['width'];
            $indx =  $this->findIndex($arrayRightCollection,$value);
            if(!isset($indx)){
                $arrayRightCollection[$value]=[$index];
            }
            else {
                $arrayRightCollection[$indx][] = $index;
            }

        }
        
        //------------------------------------------------------
        //loop found collection
        foreach ($arrayRightCollection as $leftVal => $indexes) 
        {
                $len =  sizeof($indexes);
                if( $len <= 1 ) { continue;}

                for($n=($len-1);$n>=1;$n--)
                {
                    $index=             $indexes[$n];
                    $properties =       $obj['content'][$index];
                    $fontId =           $properties['fontId'];

                    $indexPrev=         $indexes[$n-1];
                    $propertiesPrev =   $obj['content'][$indexPrev];
                    $fontIdPrev =         $propertiesPrev['fontId'];

                    if($fontId <> $fontIdPrev) 
                    {
                        continue;
                    }

                    //max spacing between sections
                    if(abs($properties['top'] - ($propertiesPrev['top'] + $propertiesPrev['height']) ) > $this->maxTextYSeparator)                      
                    {
                        continue;  
                    }

                    digi_pdf_to_html::mergeBlocks($obj,$indexPrev,$index,false);  

                }
        }

        $obj['content'] = array_values($obj['content']); //re-index data
        //------------------------------------------------------ 


    }
   

    //#################################################
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
    //#################################################



}

?>