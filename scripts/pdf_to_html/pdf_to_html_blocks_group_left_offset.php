<?php

class pdf_to_html_blocks_group_left_offset
{

    static private  $maxBlockYSeparator =  8; //max spacing between 2 lines 

    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
        //-----------------------------------------------
        //group all identical 'left' property values (for both image and text-nodes) together and gather the relatied index values from the base-data object
        $arrayLeftCollection = [];

        foreach ($obj['content'] as $index => $item) 
        {
            $value = (string)$item["left"];
            if (!isset($arrayLeftCollection[$value]))  {$arrayLeftCollection[$value] = []; }
            $arrayLeftCollection[$value][] = $index;
        } 

        foreach ($arrayLeftCollection as $leftVal => $indexes) 
        {
            if(sizeof($indexes) <= 1) { continue;}

            $len =  sizeof($indexes);
            for ($i = ($len-1); $i >= 1 ; $i--) 
            {
                    $index =                $indexes[$i];
                    $properties =           $obj['content'][$index];

                    $indexPrev =            $indexes[$i - 1];
                    $propertiesPrev =       $obj['content'][$indexPrev];

                    $prevTop = $propertiesPrev['top'] + $propertiesPrev['height'];
                    if( abs($properties['top'] - $prevTop) > self::$maxBlockYSeparator  )                                                                           { continue; }
                    if( $properties['groupNumber'] > 0 && $propertiesPrev['groupNumber'] > 0 && $properties['groupNumber'] <> $propertiesPrev['groupNumber']   )    { continue; }

                    $groupId=0;
                    if($properties['groupNumber'] > 0 )         { $groupId = $properties['groupNumber'];}
                    elseif($propertiesPrev['groupNumber'] > 0)  { $groupId = $propertiesPrev['groupNumber'];}
                    else                                        { $groupId = digi_pdf_to_html::getNewGroupNumber($obj);}


                    $obj['content'][$index]['groupNumber'] = $groupId;
                    $obj['content'][$indexPrev]['groupNumber'] = $groupId;

            }

        }

    }
    //#####################################################################

}

?>