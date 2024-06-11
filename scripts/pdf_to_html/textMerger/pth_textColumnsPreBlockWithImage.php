<?php
declare(strict_types=1);
/*
    - merges text columns
    - must have same font-size
    - a max seperation between them ($maxTextColumnSeparator)
    - same top offset (with margin $margin )

     ##########################################################################################
    SCENARIO

                |   --------------
                |   ----------
        [IMG]   |   ----- ------
                |   ---------------
    ____________|

        ---- ----
        --- -----
        --- -----
        ----- ---
        ---- ----

*/


class pth_textColumnsPreBlockWithImage
{    
    private  $maxTextColumnSeparator =  30; 
    private  $marginRight = 8; 
    private  $marginTop =   2; //margin for top-value; 
    
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
        
        $nodes = $obj['nodes'];


        foreach ($nodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            $nodes[$index]['maxLeft'] = $boundary['maxLeft'];      
        }

        $arrayRightCollection =    digi_pdf_to_html::collectPropertyValues($nodes,"maxLeft",$this->marginRight);


        foreach ($arrayRightCollection as $maxLeft => $indexes) 
        {
            if(sizeof($indexes)<=1){continue;}

            //last  node must be text-node
            $lastIndex = end($indexes);
            if($nodes[$lastIndex]['tag'] !== "text")   {continue;}

            //firstnode must be image
            if($nodes[$indexes[0]]['tag'] !== "image") {continue;}

            //group-top
            $groupTop = digi_pdf_to_html::returnMinMaxProperyValue("top",$indexes);

            $textNodes =  digi_pdf_to_html::returnProperties("tag","text",false);  

            foreach ($textNodes as $index => $properties) 
            {
                $boundary = digi_pdf_to_html::returnBoundary([$index]);
                if($boundary['left'] <= $maxLeft)                                        { continue; } //the next column must have a larger left-position
                if( $boundary['top'] > $groupTop)                                        { continue; } //image mmust be equal or higher than next text column      
                if( abs($boundary['left'] - $maxLeft) > $this->maxTextColumnSeparator)   { continue; } //spacing to the next line must be within range/allowence

                $node = $nodes[$lastIndex];

                if(!digi_pdf_to_html::textNodesAreMergable($node,$properties) ) { continue ; }

                digi_pdf_to_html::mergeNodes($lastIndex,$index); 
                $this->execute($obj);
                return;
            }
        }
    }
    //############################################################
    
    
   


}

?>