<?php
declare(strict_types=1);
/*
    - merges text columns
    - must have same font-size
    - a max seperation between them ($maxTextColumnSeparator)
    - same top offset (with margin $margin )

     ##########################################################################################
     SCENARIO A ( used in method ->scenario_a() )
     3 nodes , with the same top-value, and a maximum distance inbetween them. In this case node 2 (bottom block) and node 3 will be merged.

        -----------------       --------------
        -----------------       ---------------
        -----------------       ---------
        -----------------       ------------
        ----------
        -----------------       
        ----------

        #### new title ###

        -----------------
        -------
        -----------------
        ------
        -----------------

    ##########################################################################################
    SCENARIO B ( used in method ->scenario_b() )
    2 nodes , with the same top-value, and a maximum distance inbetween them. In this case node 1 (left block) and node 2 will be merged.

        -----------------       --------------
        -----------------       ---------------
        -----------------       ---------
        -----------------       ------------
        ----------
        -----------------       IMG
        ----------


*/


class pth_textColumns
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

    private function execute(&$obj)
    {
        $this->scenario_a($obj);
        $this->scenario_b($obj);
    }

    //#####################################################################
    private function scenario_a(&$obj):void
    {

        $textNodes =  digi_pdf_to_html::returnProperties("tag","text",false);  
        //--------------------------------------------
        //get maxLeft position
        foreach ($textNodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            $textNodes[$index]['maxLeft'] = $boundary['maxLeft'];
        }

        $arrayRightCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"maxLeft",$this->marginRight);

        foreach ($arrayRightCollection as $maxLeft => $indexes) 
        {
                //get (lowest) topvalue of $indexes. column-layouts must have same top)
                $minTop = digi_pdf_to_html::returnMinMaxProperyValue("top",$indexes,false);

                //loop all nodes
                foreach ($textNodes as $index => $properties) 
                {
                    $boundary = digi_pdf_to_html::returnBoundary([$index]);
                    if($boundary['left'] <= $maxLeft)                                      { continue; } //the next column must have a larger left-position
                    if( abs($boundary['top'] - $minTop) > $this->marginTop)                { continue; } //top top-position does not match         
                    if( abs($boundary['left'] - $maxLeft) > $this->maxTextColumnSeparator) { continue; } //spacing to the next line must be within range/allowence
                   
                    $lastIndex = end($indexes);

                    if($properties['fontSize'] <> $obj['content'][$lastIndex]['fontSize']) { continue; }//fontsize does not match

                    digi_pdf_to_html::mergeNodes($lastIndex,$index); 
                    $this->execute($obj);
                    return;
                }
        }

    }
    //#####################################################################
    private function scenario_b(&$obj):void
    {

        $textNodes =                    digi_pdf_to_html::returnProperties("tag","text",false);  

        //-----------------------------------------------
        //collect all identical 'top' property values (for text-nodes) together
        $arrayTopCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"top",$this->marginTop);

        foreach ($arrayTopCollection as $top => $indexes) 
        {
                $len = sizeof($indexes);
                if( $len <= 1 ) { continue; }

                for( $n=0; $n < $len; $n++ )
                {
                    $index=         $indexes[$n];
                    $node =         $obj['content'][$index];
                    $boundary=      digi_pdf_to_html::returnBoundary([$index]);
                    $index2=        null;
                    $node2=         null;

                    if(isset($indexes[$n+1]))
                    {
                        $index2=        $indexes[$n+1];;
                        $node2=         $obj['content'][$index2];
                        $boundary2=     digi_pdf_to_html::returnBoundary([$index2]);  
                        
                        //make sure font-size is the same
                        if( $node['fontSize'] <> $node2['fontSize'] ) { continue; }

                         //spacing to the next line must be within range/allowence
                        if( abs($boundary2['left'] - $boundary['maxLeft']) > $this->maxTextColumnSeparator) { continue; }

                        digi_pdf_to_html::mergeNodes($index,$index2); 
                        $this->execute($obj);
                        return;
                    }
                }

        }
    }
    //#####################################################################
    

    
   


}

?>