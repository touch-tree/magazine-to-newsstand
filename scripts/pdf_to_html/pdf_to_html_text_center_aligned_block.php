<?php

class pdf_to_html_text_center_aligned_block
{
  
 
    static private  $maxHeightThreshold=            60; 
    static private  $maxcenterMarginThreshold=      3;
    static private  $maxTextYSeparator =            8;    
    //#####################################################################
    static public function process(&$obj):void
    {	
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        $arrayBlocks = [];

        //--------------------
        //only collect un-grouped text nodes
        $len = sizeof( $obj['content'] );  
        for( $n = 0; $n < $len; $n++ )
        {
           if( $obj['content'][$n]['groupNumber'] > 0 )                     { continue; }   //already grouped elsewhere
           if( $obj['content'][$n]['tag'] !== "text")                       { continue; }   //ignore images
           if( $obj['content'][$n]['height'] > self::$maxHeightThreshold )  { continue; }   //previously merged texts (thus gathered a certain height), but not assigned to a group yet.
           $arrayBlocks[$n] = $obj['content'][$n];
        }

        //--------------------
        //calculate center position of texts, and link these with main indexes of the main data object $obj
        $arrayCentered = [];

        foreach ($arrayBlocks as $index => $properties) 
        {
                $centerValue = ceil(($properties['left'] + ($properties['left'] + $properties['width'])) / 2);
                $centerIndx =  self::findIndex($arrayCentered,$centerValue);
                if(!isset($centerIndx)){
                    $arrayCentered[$centerValue]=[$index];
                }
                else {
                    $arrayCentered[$centerIndx][] = $index;
                }
        }

        //--------------------
        //parse centered collection
        $arrayBlocks = [];

        foreach ($arrayCentered as $topVal => $indexes) 
        {
            if(sizeof($indexes) <=1 ) { continue; }
            $array = digi_pdf_to_html::filterSelectedIndexes($obj,$indexes); //Note that the index-numbers themselves are preserved. 
            $array = digi_pdf_to_html::sortArrayByProperty($array,"top",true);

            $block = self::returnBlockObject();
 
            foreach ($array as $index => $properties) 
            {
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
                                $block = self::returnBlockObject();
                            }

                            //----------------------------
                            //collect data
                        
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

        //--------------------------
        //group text based column layout together (will obtain a groupNumber assignment)
       
        $loop = sizeof($arrayBlocks);
        for( $n= ($loop - 1); $n >= 1; $n-- )
        {
            //previous index
            $indexPrev = $n - 1;

            //set groupNumber
            if($arrayBlocks[$indexPrev]['groupNumber'] == 0)
            {
                $arrayBlocks[$indexPrev]['groupNumber'] = digi_pdf_to_html::getNewGroupNumber($obj);
            }
            $arrayBlocks[$n]['groupNumber'] = $arrayBlocks[$indexPrev]['groupNumber'];
        
        }


        //-----------------------------------------
        //assign groupNumbers to the main $obj.
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
            for($n=0;$n<$loop;$n++){
                if($indexes[$n] == $firstIndex ) {continue;}
                unset($obj['content'][$indexes[$n]]);
            }
        }

        $obj['content'] = array_values($obj['content']);
   
        //------------------------------------------------
    
       
       



       

        
     

    }
    //#####################################################################
    static private function findIndex(array $array, int $centerValue):?int
    {
        $min = $centerValue - self::$maxcenterMarginThreshold;
        $max = $centerValue + self::$maxcenterMarginThreshold;
        for($n = $min; $n<=$max;$n++)
        {
            if(isset($array[$n])) { return $n;}
        }

        return null;
    }
    //##################################################################### 
    static private function returnBlockObject():array
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

}

?>