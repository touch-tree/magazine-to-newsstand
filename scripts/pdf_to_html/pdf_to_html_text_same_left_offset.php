<?php

class pdf_to_html_text_same_left_offset
{
    static private  $arrayBlocks =                  [];
    static private  $maxTextYSeparator =            8;     
    static private  $maxTextColumnSeparator =       20;     
    //#####################################################################
    static private function returnArrayTextPropertyWithLinkedMultipleIndexes(array $pageObj, string $prop):array
    {	
        $propValues = [];

        foreach ($pageObj as $index => $item) 
        {
            if($item['tag'] === "image") { continue; }
            $value = (string)$item[$prop];
            if (!isset($propValues[$value])) {$propValues[$value] = [];}
            $propValues[$value][] = $index;
        }

        foreach ($propValues as $p => $arr) 
        {
            
                if( sizeof($arr) <= 1 ) 
                {   
                    unset($propValues[$p]); 
                } 
                else 
                {
                    sort($propValues[$p], SORT_NUMERIC);
                }
         
        }

        ksort($propValues);
        return $propValues;
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
        $obj['usedIndexes'] =      [];
        return  $obj;
    }


    //#####################################################################
    static public function process(&$obj)
    {	
      
        $len = sizeof( $obj['content'] );  
        $arr = self::returnArrayTextPropertyWithLinkedMultipleIndexes( $obj['content'] ,"left");
        self::$arrayBlocks = [];

        foreach ($arr as $leftVal => $indexes) 
        {
                $array = digi_pdf_to_html::filterSelectedIndexes($obj,$indexes); //Note that the index-numbers themselves are preserved. 
                $array = digi_pdf_to_html::sortArrayByProperty($array,"top",true);
                
                $block = self::returnBlockObject();
 
                foreach ($array as $index => $properties) 
                {
                            //tresshold based on top-propert
                            if($block['topFinal'] > 0 and abs($properties['top'] - $block['topFinal']) > self::$maxTextYSeparator)
                            {
                                self::$arrayBlocks[] = $block;
                                $block = self::returnBlockObject();
                            }
                        
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

                            //index numbers
                            $block['usedIndexes'][] = $index;
 
                }

                self::$arrayBlocks[] = $block;
            
        }
     
        //--------------------------
   
        
        //merge text-columns if applicable (note only for n>=1)
        $loop = sizeof(self::$arrayBlocks);
        for( $n= ($loop - 1); $n >= 1; $n-- )
        {
            //previous index
            $indexPrev = $n - 1;
            
            //max distance between columns
            $leftDiff = self::$arrayBlocks[$n]['leftStart'] - self::$arrayBlocks[$indexPrev]['leftFinal'];
            if($leftDiff > self::$maxTextColumnSeparator  ) { continue; }

            //previous block must always have an equal or lower top-value (e.g. section for the header text-paragraph)
            $topDiff = self::$arrayBlocks[$n]['topStart'] - self::$arrayBlocks[$indexPrev]['topStart'];
            if($topDiff < 0) { continue; }

            //previous block must always be equal or highter
            $heightDiff = self::$arrayBlocks[$indexPrev]['height'] - self::$arrayBlocks[$n]['height']; 
            if($heightDiff < 0) { continue; }

            //previous block must always have an equal or highter max-top value
            $maxTopDiff = self::$arrayBlocks[$indexPrev]['topFinal'] - self::$arrayBlocks[$n]['topFinal'] ;
            if($maxTopDiff < 0) { continue; }

            //copy properties from $n -> $indexPrev; 
            self::$arrayBlocks[$indexPrev]['topStart'] =    min( [self::$arrayBlocks[$indexPrev]['topStart'],self::$arrayBlocks[$n]['topStart'] ] );
            self::$arrayBlocks[$indexPrev]['topFinal'] =    max( [self::$arrayBlocks[$indexPrev]['topFinal'],self::$arrayBlocks[$n]['topFinal'] ] );
            self::$arrayBlocks[$indexPrev]['leftStart'] =   min( [self::$arrayBlocks[$indexPrev]['leftStart'],self::$arrayBlocks[$n]['leftStart'] ] );
            self::$arrayBlocks[$indexPrev]['leftFinal'] =   max( [self::$arrayBlocks[$indexPrev]['leftFinal'],self::$arrayBlocks[$n]['leftFinal'] ] );   
            self::$arrayBlocks[$indexPrev]['width'] =       self::$arrayBlocks[$indexPrev]['leftFinal'] - self::$arrayBlocks[$indexPrev]['leftStart'] ;
            self::$arrayBlocks[$indexPrev]['height'] =      self::$arrayBlocks[$indexPrev]['topFinal'] - self::$arrayBlocks[$indexPrev]['topStart'] ;
            self::$arrayBlocks[$indexPrev]['content'].=     self::$arrayBlocks[$n]['content'];
            self::$arrayBlocks[$indexPrev]['usedIndexes'] = array_merge(self::$arrayBlocks[$indexPrev]['usedIndexes'],self::$arrayBlocks[$n]['usedIndexes']);
            unset(self::$arrayBlocks[$n]);
            
            
        }
        
        //print_r(self::$arrayBlocks);exit;
        //---------------------
        //assign group numbers
        foreach (self::$arrayBlocks as $key => $properties) 
        {
            $indexes =  $properties['usedIndexes'];
            if(sizeof($indexes)==0) {continue;}
            $firstIndex = min ($indexes);
            
            $obj['content'][$firstIndex]['top']=        $properties['topStart'];
            $obj['content'][$firstIndex]['left']=       $properties['leftStart'];
            $obj['content'][$firstIndex]['width']=      $properties['width'];
            $obj['content'][$firstIndex]['height']=     $properties['height'];
            $obj['content'][$firstIndex]['content']=    $properties['content'];

            $loop = sizeof($indexes);
            for($n=0;$n<$loop;$n++)
            {
                if($indexes[$n] == $firstIndex ) {continue;}
                unset($obj['content'][$indexes[$n]]);
            }            
        }

        $obj['content'] = array_values($obj['content']);

      
    }
    //#####################################################################

}

?>