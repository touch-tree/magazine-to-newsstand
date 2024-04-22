<?php

class pdf_to_html_text_group_left_offset
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
            if(sizeof($indexes) <= 1) { continue;}

            $objClone = digi_pdf_to_html::filterSelectedIndexes($obj,$indexes);        //Note that the index-numbers themselves are preserved. 
            $objClone = digi_pdf_to_html::sortArrayByProperty($objClone,"top",true);

            $block =     self::returnBlock();

            foreach ($objClone as $index => $properties) 
            {
                           //------------------------------------------
                            //reset block or not
                            $resetBlock = false;

                            //tresshold based on top-property
                            if($block['topFinal'] > 0 and abs($properties['top'] - $block['topFinal']) > self::$maxTextYSeparator)  
                            {
                                 $resetBlock = true;  
                            }

                            //font type change
                            if($block['fontId'] > 0 and $block['fontId'] <> $properties['fontId'] )                                
                            { 
                                $resetBlock = true;  
                            }

                            if($resetBlock)
                            {
                                $arrayBlocks[] = $block;
                                $block = self::returnBlock();
                            }

                            //----------------------------
                            //collect data for $block
                        
                            //top start
                            if($block['topStart'] == 0 || $properties['top'] < $block['topStart'])
                            {
                                $block['topStart'] = $properties['top'];
                            }
                            
                            //top final
                            $topFinal = $properties['top'] + $properties['height'] ;
                            if($topFinal > $block['topFinal'] )     
                            {
                                $block['topFinal']=$topFinal;
                            }

                            //left start
                            if($block['leftStart'] == 0 || $properties['left'] < $block['leftStart'])
                            {
                                $block['leftStart'] = $properties['left'];
                            } 

                            //left final
                            $leftFinal = $properties['left'] + $properties['width'] ;
                            if($leftFinal > $block['leftFinal'] )     
                            {
                                $block['leftFinal']=$leftFinal;
                            }  

                            //width and height
                            $block['width'] =  $block['leftFinal'] - $block['leftStart'];
                            $block['height'] = $block['topFinal'] - $block['topStart'];

                            //content
                            $block['content'] .= $properties['content'];

                            //font
                            $block['fontId'] = $properties['fontId'];

                            //index numbers
                            $block['usedIndexes'][] = $index;

            }

            $arrayBlocks[] = $block;

        }

        //----------------------------------
        //assign content to main data-object
        foreach ($arrayBlocks as $indx => $properties) 
        {
            $indexes =  $properties['usedIndexes'];
            if(sizeof($indexes)==0) { continue; }
            $firstIndex = $indexes[0];
            $obj['content'][$firstIndex]['top']=            $properties['topStart'];
            $obj['content'][$firstIndex]['left']=           $properties['leftStart'];
            $obj['content'][$firstIndex]['width']=          $properties['width'];
            $obj['content'][$firstIndex]['height']=         $properties['height'];
            $obj['content'][$firstIndex]['content']=        $properties['content'];
            $obj['content'][$firstIndex]['groupNumber']=    $properties['groupNumber'];

            $loop = sizeof($indexes);
            for($n=0;$n<$loop;$n++)
            {
                if($indexes[$n] == $firstIndex ) {continue;}
                unset($obj['content'][$indexes[$n]]);
            }
        }

        $obj['content'] = array_values($obj['content']); //re-index main data object

    }
    //#####################################################################

}

?>