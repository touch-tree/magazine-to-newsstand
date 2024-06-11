<?php
declare(strict_types=1);
/*
    - merges text columns
    - must have same font-size
    - same bottom position of block
    - a max seperation between them ($maxTextColumnSeparator)
    - an image above it

     ##########################################################################################
    SCENARIO

        ---- ----       _____________
        -- ------       |           |
                        |           |
        #title          |   IMG     |
                        _____________
        --- -----       _____________
        --- -----       |   img     |
        ---- ----       _____________
        ----- ---       --- --- -----
        ---- ----       -- - --- ----
        --- -----       #sub-note

*/


class pth_textColumnsBottomAligned
{    
    private     $marginBottom = 8;
    private     $maxTextColumnSeparator =  30; 
    private     $marginLeft =  3; 
    
    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();

        //-------------------------------
        $this->execute($obj);       
    }
    
    //#####################################################################
    private function execute(&$obj):void
    {
        $textNodes = digi_pdf_to_html::returnProperties("tag","text",false);   
        foreach ($textNodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            $textNodes[$index]['maxTop'] = $boundary['maxTop'];      
        }    

        $arrayBottomCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"maxTop",$this->marginBottom);
        $textNodes =                digi_pdf_to_html::sortNodesByProperty($textNodes,"top",false); //sort nodes from bottom to top

        foreach ($arrayBottomCollection as $maxTop => $indexes) 
        {
            if(sizeof($indexes)<=1){ continue; }
            $nodes = digi_pdf_to_html::returnNodesFromIndexes($indexes);
            $nodes = digi_pdf_to_html::sortNodesByProperty($nodes,"left");

            $arrayKeys =    array_keys($nodes);
            $len =          sizeof($arrayKeys);
            for($n=0;$n<$len;$n++)
            {
                if(!isset($arrayKeys[$n+1])){ break; }
                
                $index =        $arrayKeys[$n];
                $properties =   $nodes[$index];
                $boundary =     digi_pdf_to_html::returnBoundary([$index]);

                $index2 =        $arrayKeys[$n+1];
                $properties2 =   $nodes[$index2];

                $diffX = abs($properties2['left']-$boundary['maxLeft']);
                if($diffX > $this->maxTextColumnSeparator) { continue;}

                //- text from bottom to top for the same left value
                //- text-top not highter than the first column
                foreach ($textNodes as $nodeIndex => $nodeProperties) 
                {
                    $nodeBoundary =   digi_pdf_to_html::returnBoundary([$nodeIndex]);

                    if($nodeProperties['top']  < $boundary['top'])      { continue; } 
                    if($nodeProperties['top']  > $boundary['maxTop'])   { continue; }    
                    if($nodeBoundary['maxTop'] < $boundary['top'])      { continue; }  
                    if($nodeBoundary['maxTop'] > $boundary['maxTop'])   { continue; }  
                    $diffX = abs($nodeProperties['left'] -  $properties2['left']);
                    if($diffX > $this->marginLeft )                     { continue; }

                    //node above must be an image
                    $aboveTop =     $nodeProperties['top']-50;
                    $aboveLeft =    sys::posInt((round(($nodeBoundary['maxLeft'] + $nodeProperties['left']) / 2)));
                    $nodeAbove = digi_pdf_to_html::returnNodeFromCoordinates($aboveTop,$aboveLeft);
                    if(!isset($nodeAbove))                              { continue;}
                    if($nodeAbove['tag']==="text")                      { continue;} 
                    
                    if(!digi_pdf_to_html::textNodesAreMergable($properties,$nodeProperties) ) { continue ; }

                    digi_pdf_to_html::mergeNodes($index,$nodeIndex); 
                    $this->execute($obj);
                    return;
                }
            }
        }
    }
    //############################################################
    
    
   


}

?>