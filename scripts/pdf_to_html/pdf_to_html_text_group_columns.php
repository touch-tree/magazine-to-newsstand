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
        //group all identical 'top' property values (for text-nodes) together and gather the relatied index values from the base-data object
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
        $foundColumnGroups = [];
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
                    if($leftDiff > self::$maxTextColumnSeparator  )         { $isNewGroup = true; continue; }
                    if($properties['fontId'] <> $propertiesPrev['fontId'])  { $isNewGroup = true; continue; } //assume columns have the same fontId
                    if($properties['height']  > $propertiesPrev['height'])  { $isNewGroup = true; continue; } //assume that previous column within a group is always higher than any later one

                    if($isNewGroup)
                    {
                        $groupId =      $propertiesPrev['groupNumber'];
                        if($groupId == 0)
                        {
                            $groupId = digi_pdf_to_html::getNewGroupNumber($obj);
                        }
                       
                        $isNewGroup =   false;
                        $foundColumnGroups[]= $groupId;
                    }

                    $obj['content'][$index]['groupNumber'] =        $groupId;
                    $obj['content'][$indexPrev]['groupNumber'] =    $groupId;
                }

 

        }

        //------------------------------------------------
        //merger of the texts for same groups
        $foundColumnGroups = array_values(array_unique($foundColumnGroups));
        
        if(sizeof($foundColumnGroups)>0)
        {
            $len = sizeof( $foundColumnGroups );
            for($n=0;$n<$len;$n++)
            {
                $groupId =      $foundColumnGroups[$n];
                $indexToMove =  null;

                $keys =     array_keys($obj['content']);
                $len2 =     sizeof($keys);
                for($i = ($len2-1); $i >= 1 ; $i--) 
                {
                    $index = $keys[$i];
                    if($obj['content'][$index]['groupNumber'] <> $groupId ) { continue; }
                    
                        if(!isset($indexToMove)) { $indexToMove = $index; continue ;}
                
                        $obj['content'][$index]['content'] .=  $obj['content'][$indexToMove]['content']; 
                        $obj['content'][$index]['left']     =  min([$obj['content'][$index]['left'],$obj['content'][$indexToMove]['left']]);
                        $obj['content'][$index]['top']      =  min([$obj['content'][$index]['top'],$obj['content'][$indexToMove]['top']]);

                        //calc new width
                        $finalLeft1 = $obj['content'][$index]['left'] + $obj['content'][$index]['width'];
                        $finalLeft2 = $obj['content'][$indexToMove]['left'] + $obj['content'][$indexToMove]['width'];
                        $obj['content'][$index]['width'] = max([$finalLeft1,$finalLeft2]) - $obj['content'][$index]['left'];

                        //calc new height
                        $finalTop1 = $obj['content'][$index]['top'] + $obj['content'][$index]['height'];
                        $finalTop2 = $obj['content'][$indexToMove]['top'] + $obj['content'][$indexToMove]['height'];
                        $obj['content'][$index]['height'] = max([$finalTop1,$finalTop2]) - $obj['content'][$index]['top'];

                        unset($obj['content'][$indexToMove]);

                        $indexToMove = $index;  //for the next (previous) column
                }
            }
            
        }
        //------------------------------------------------

        
    }
    //#####################################################################

}

?>