<?php
declare(strict_types=1);
//#####################################################################
class pth_groupTextIntersectWithBoundary
{
    

    public function __construct(&$obj)
    {
        
        //--------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);

        $objBlocks = digi_pdf_to_html::returnProperties($obj['content'],"tag","text");
        $keys = array_keys($objBlocks);
        $len = sizeof($keys);


        for( $n=0; $n < $len; $n++ )
        {
            $index =        $keys[$n];
            $properties =   $objBlocks[$index];

            $indexPrev = null;
            $propPrev =  null;
            if(isset($keys[$n-1]))
            {
                $indexPrev = $keys[$n-1];
                $propPrev =  $objBlocks[$indexPrev];         
            }

            $indexNext = null;
            $propNext=   null;
            if(isset($keys[$n+1]))
            {
                $indexNext = $keys[$n+1];
                $propNext =  $objBlocks[$indexNext];         
            }


            $indexesToGroup=[];
            if($this->touchesBoundary( $propPrev, $properties))                                 { $indexesToGroup[]=$index; $indexesToGroup[]=$indexPrev; }
            if(sizeof($indexesToGroup)==0 && $this->touchesBoundary( $properties, $propNext))   { $indexesToGroup[]=$index; $indexesToGroup[]=$indexNext;  }

            sort($indexesToGroup);
            $len2 = sizeof($indexesToGroup);
            if($len2 <= 1) { continue; }

            $arrayGroupId=[];
            for( $i=0; $i < $len2; $i++ )
            {
                $groupId = $obj['content'][$indexesToGroup[$i]]['groupNumber'];  
                $arrayGroupId[] = $groupId;
            }

            if( !in_array(0,$arrayGroupId) ) { continue; }
            $groupId = max($arrayGroupId);
            if($groupId == 0 ) {$groupId =  digi_pdf_to_html::getNewGroupNumber($obj);}
            for( $i=0; $i < $len2; $i++ )
            {
                if($obj['content'][$indexesToGroup[$i]]['groupNumber'] == 0)
                {
                    $obj['content'][$indexesToGroup[$i]]['groupNumber'] = $groupId;   
                } 
                
            }
            
            


        }

        //-----------------------------------------------------------------------
        //the second variant
        
        $arrayBlocks = [];
        foreach ($obj['content'] as $index => $properties) 
        {
                    if($properties['tag'] === "image") { continue; }

                    foreach ($arrayBlocks as $index2 => $properties2) 
                    {
                            if(
                                $properties['left'] >= $properties2['left'] 
                                && $properties['left'] <= ($properties2['left'] + $properties2['width'])
                                && $properties['top'] >= $properties2['top'] 
                                && $properties['top'] <= ($properties2['top'] + $properties2['height'])
                            )
                            {
                                    $groupId1 = $properties['groupNumber'];  
                                    $groupId2 = $properties2['groupNumber'];  
                                    if($groupId1 > 0 and $groupId2>0) {continue;}

                                    if($groupId1 > 0)        {$groupId =  $groupId1;}
                                    elseif($groupId2 > 0)    {$groupId =  $groupId2;}
                                    else                     {$groupId =  digi_pdf_to_html::getNewGroupNumber($obj);}
                
                                    $obj['content'][$index]['groupNumber'] = $groupId;
                                    $obj['content'][$index2]['groupNumber'] = $groupId;
                            }
                    }

            $arrayBlocks[$index]=$properties;  
        }
        
        //---------------------------------------------------------------------
        
        
        
    }


    //###################################################
    private function touchesBoundary(?array $propTop=null, ?array $propBottom = null ):bool
    {
        if(!isset($propTop) || !isset($propBottom) ) {return false;}

        //---------------------------
        //top-left orientated
        if(
            $propTop['left'] >= $propBottom['left'] 
            && $propTop['left'] <= ($propBottom['left'] + $propBottom['width'])
            && $propTop['top'] >= $propBottom['top'] 
            && $propTop['top'] <= ($propBottom['top'] + $propBottom['height'])
          ) { return true; }

        //---------------------------
        //bottom right orientated
        $right =   $propTop['left'] +  $propTop['width'];
        $bottom =  $propTop['top'] +  $propTop['height'];
        if(
            $right >= $propBottom['left'] 
            && $right <= ($propBottom['left'] + $propBottom['width'])
            && $bottom >= $propBottom['top'] 
            && $bottom <= ($propBottom['top'] + $propBottom['height'])
          ) { return true; }


          return false;
    }
   //###################################################


}

?>