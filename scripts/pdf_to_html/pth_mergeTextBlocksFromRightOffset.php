<?php
declare(strict_types=1);
//########################################################

class pth_mergeTextBlocksFromRightOffset
{
    private  $maxBlockYSeparator =  8; //max spacing between 2 sections 
    private  $maxMarginThreshold=   30;

    public function __construct(&$obj)
    {
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj); 

     

        //---------------------------------------------
        //get right-sided collection
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
                    if(abs($properties['top'] - ($propertiesPrev['top'] + $propertiesPrev['height']) ) > $this->maxBlockYSeparator)                      
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
        $min = $val - $this->maxMarginThreshold;
        $max = $val + $this->maxMarginThreshold;
        for($n = $min; $n<=$max;$n++)
        {
            if(isset($array[$n])) { return $n;}
        }

        return null;
    }
    //###########################################################


}

?>