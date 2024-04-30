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

        //-------------
        //sort obj by largest area size
        $clone = $obj;
        foreach ($clone['content'] as $index => $properties) 
        {
            $clone['content'][$index]['area'] = $properties['width'] * $properties['height'];   
        }
        

        $arrayBlocks = [];
     
        foreach ($clone['content'] as $index => $properties) 
        {

            if($properties['tag'] === "image"){ continue; }
            //------------------------------------------------------------
            //get boundary data
            $groupId = $properties['groupNumber'];
            if($groupId > 0)
            {
                $arr = digi_pdf_to_html::returnProperties($clone['content'],"groupNumber",$groupId);
                $mainBoundary = digi_pdf_to_html::getTextBoundaryBlock($clone,array_keys($arr));
            }
            else
            {
               
                $mainBoundary = digi_pdf_to_html::getTextBoundaryBlock($clone,[$index]);
            }


            //------------------------------------------------------------
            foreach ($arrayBlocks as $index2 => $properties2) 
            {
                if($index == $index2) {continue;}
                $subBoundary = digi_pdf_to_html::getTextBoundaryBlock($clone,[$index2]);

                if($this->blockInBoundary($mainBoundary, $subBoundary) )
                {
                    
                    echo "---------------------------------------\n";
                    echo "0) index:". $index." and index2: $index2 \n";
                    echo "1)". $clone['content'][$index]['content']." \n";
                    echo "2)". $clone['content'][$index2]['content']."\n";

                }
                else if($this->blockInBoundary($subBoundary, $mainBoundary) )
                {
                    echo "---------------------------------------\n";
                    echo "0) index:". $index." and index2: $index2 \n";
                    echo "A)". $clone['content'][$index]['content']." \n";
                    echo "B)". $clone['content'][$index2]['content']."\n";        
                }

            }



            $arrayBlocks[$index]=$mainBoundary;


           // print_r($boundary);exit;


                    /*
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
                    */

           
            
        }

        echo "tothier";exit;
        

    }

    //###################################################
    private function blockInBoundary(array $properties, array $objBoundary ):bool
    {
        if(
            
            $properties['left'] <  $objBoundary['left'] 
            || $properties['left'] >  $objBoundary['maxLeft']
            || $properties['top'] <   $objBoundary['top']
            || $properties['top'] >   $objBoundary['maxTop'] 
          ) { return false; }
          return true;
    }
   


}

?>