<?php

class pdf_to_html_text_group_columns
{
    static private  $maxTextColumnSeparator =  20;     

    //#####################################################################
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);

        //-----------------------------------------------
        //group all identical 'top' property values (for text-nodes) together and gather the relatied index values from tghe base-data object
        $arrayTopCollection = [];

        foreach ($obj['content'] as $index => $item) 
        {
            if($item['tag'] === "image") { continue; }
            $value = (string)$item["top"];
            if (!isset($arrayTopCollection[$value]))  {$arrayTopCollection[$value] = []; }
            $arrayTopCollection[$value][] = $index;
        }

        //-------------------------------------------------
        //loop found collection
        foreach ($arrayTopCollection as $topVal => $indexes) 
        {
                if(sizeof($indexes) <= 1) { continue;}

                $objClone = digi_pdf_to_html::filterSelectedIndexes($obj,$indexes);        //Note that the index-numbers themselves are preserved. 
                $objClone = digi_pdf_to_html::sortArrayByProperty($objClone,"left",true);

    
                $keys = array_keys($objClone);
                $len =  sizeof($keys);
                $isNewGroup = true;
                for ($i = ($len-1); $i >= 1 ; $i--) 
                {
                    $index =                $keys[$i];
                    $properties =           $objClone[$index];

                    $indexPrev =            $keys[$i - 1];
                    $propertiesPrev =       $objClone[$indexPrev];

                    //max distance between columns
                    $leftDiff = $properties['left'] - ($propertiesPrev['left'] + $propertiesPrev['width'] );
                    if($leftDiff > self::$maxTextColumnSeparator  ) { $isNewGroup = true; continue; }

                    if($isNewGroup){
                        $groupId =      digi_pdf_to_html::getNewGroupNumber($obj);
                        $isNewGroup =   false;
                    }

                    $obj['content'][$index]['groupNumber'] = $groupId;
                    $obj['content'][$indexPrev]['groupNumber'] = $groupId;
                }

        }

    }
    //#####################################################################

}

?>