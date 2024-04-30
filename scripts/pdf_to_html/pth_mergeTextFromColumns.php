<?php
declare(strict_types=1);

/*
    Group text sections from text-columns
 
*/

class pth_mergeTextFromColumns
{
    private  $maxTextColumnSeparator =  30;  

    public function __construct(&$obj)
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

        foreach ($arrayTopCollection as $topVal => $indexes) 
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

                //max distance between columns
                $leftDiff = abs(   $properties['left'] - ($propertiesPrev['left'] + $propertiesPrev['width'] )  );
                if($leftDiff > $this->maxTextColumnSeparator  )
                {
                     continue; 
                }

                //assume that previous column within a group is always higher than any later one
                if($properties['height']  > $propertiesPrev['height'])  
                {
                     continue; 
                } 

              
                digi_pdf_to_html::mergeBlocks($obj,$indexPrev,$index,false);    

            }

        }

        $obj['content'] = array_values($obj['content']); //re-index data

    }
   

    //#################################################



}

?>