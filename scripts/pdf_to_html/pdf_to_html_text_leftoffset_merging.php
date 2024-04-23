<?php

class pdf_to_html_text_leftoffset_merging
{

    static private  $maxTextYSeparator =  8; //max spacing between 2 lines 

    //#####################################################################

    static private function returnBlock():array
    {
        $obj =                    [];
        $obj['topStart'] =         0;
        $obj['topFinal'] =         0;
        $obj['leftStart'] =        0;
        $obj['leftFinal'] =        0;
        $obj['width'] =            0;
        $obj['height'] =           0;
        $obj['content'] =          "";
        $obj['fontId'] =           0;
        $obj['usedIndexes'] =      [];
        $obj['groupNumber'] =      0;
        return  $obj;
    }

    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
        //-----------------------------------------------
        //group all identical 'left' property values (for text-nodes) together and gather the relatied index values from the base-data object
        $arrayLeftCollection = [];

        foreach ($obj['content'] as $index => $item) 
        {
            if($item['tag'] === "image") { continue; }
            $value = (string)$item["left"];
            if (!isset($arrayLeftCollection[$value]))  {$arrayLeftCollection[$value] = []; }
            $arrayLeftCollection[$value][] = $index;
        }

        //-------------------------------------------------
        //loop found collection
        $arrayBlocks = [];

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