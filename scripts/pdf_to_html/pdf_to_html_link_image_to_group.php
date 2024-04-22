<?php

class pdf_to_html_link_image_to_group
{

    static private $maxDistance =               30;     //max spacing between text -> image
    static private $overlappingTopMargin =      5; 
    static private $overlappingLeftMargin =     5; 
    //#####################################################################
    
    static public function process(&$obj):void
    {	
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
       
        //----------------------------------------------
        //settings
        $keys = array_keys($obj['content']);
        $len  = sizeof($keys);
        $arrayHandledGroup = [];
        
        //-----------------------------------------------
        //find images situated below group-textblocks. If found, assign the group-number to the image node
        for( $n=($len-1); $n >= 0; $n-- )
        {
            $index =    $keys[$n];
            if($obj['content'][$index]['tag'] !== "text") { continue;}
            $groupId =  $obj['content'][$index]['groupNumber'];

            if( $groupId== 0 )                          { continue; }
            if(in_array( $groupId, $arrayHandledGroup)) { continue; }
           
            $arrayHandledGroup[] = $groupId;            //group-component with the highest top-value
            $finalTop =     $obj['content'][$index]['top'] + $obj['content'][$index]['height'];
            $left =         $obj['content'][$index]['left'];
    
            foreach ($obj['content'] as $index2 => $properties2) 
            {
                $minLeft = $left - self::$overlappingLeftMargin;

                if($obj['content'][$index2]['tag'] !== "image")                             { continue; }   
                if($obj['content'][$index2]['groupNumber'] > 0)                             { continue; }  
                if($obj['content'][$index2]['top'] < $finalTop  )                           
                {
                        $diff = abs($finalTop - $obj['content'][$index2]['top']);
                        if($diff >= self::$overlappingTopMargin)
                        {
                            continue;    
                        }     
                }
                if($obj['content'][$index2]['left'] < $minLeft  )                           { continue; }

                if( abs($obj['content'][$index2]['top'] - $finalTop) > self::$maxDistance   ) { continue; } 
                $obj['content'][$index2]['groupNumber'] = $groupId;
                break;
            }
        }
       
        //################################################################

    }
    






}

?>