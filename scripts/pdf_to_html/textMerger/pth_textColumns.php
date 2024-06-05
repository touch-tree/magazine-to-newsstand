<?php
declare(strict_types=1);
/*
    Find and merging of text nodes that are part of a column-layout
    //##################################################################################
    
        ### title ####          ---------------
        -----------------       ---------
        -----------------       ------------
        ----------              --------- -----------
        -----------------       --------
        ----------

         ### title #### 

        -----------------       
        -----------------      
        -----------------       
        ----------
        -----------------       
        ----------

    //##################################################################################

*/


class pth_textColumns
{    
    private  $maxTextColumnSeparator =      30;     //max-x-distance between columns
    private  $marginTop =                   2;      //margin for top-value of nodes; (column layout are assumed to have the same top offset value) 
    private  $marginLeft =                  3;      //left margin of nodes
    private  $maxSectionYSeparator =        80;     //max-y-distance between nodes that are assumed to belong to the same column.
    
    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();
        $this->execute($obj);       
    }
    
    //#####################################################################
    private function execute(&$obj):void
    {
        $textNodes =  digi_pdf_to_html::returnProperties("tag","text",false); 

        $objTree = $this->gatherVerticalTree($textNodes);    
        $objTree = $this->gatherHorizontalTree($objTree);

    
        foreach ($objTree as $groupIndex => $blocks) 
        {
            
            foreach ($blocks as $n => $indexes) 
            {
               
                if(!isset($blocks[$n+1])) { break; } //nothing to merge with in the next column
            
                $lastIndex =        end($indexes); 
                $nextIndex =        reset($blocks[$n+1]);


                
             
                //check if there is another node embedded within a larger one (e.g. due to earlier merger); if so the lowest/rightmost one applies
                $boundary =  digi_pdf_to_html::returnBoundary([$lastIndex]); 
                foreach ($textNodes as $nodeIndex => $nodeProperties) 
                {
                    $embeddedBoundary = digi_pdf_to_html::returnBoundary([$nodeIndex]); 
                    if($embeddedBoundary['top'] <=  $boundary['top'])       { continue; }                    
                    if($embeddedBoundary['left'] <= $boundary['left'])      { continue; }
                    if($embeddedBoundary['left'] >= $boundary['maxLeft'])   { continue; }

                    if(digi_pdf_to_html::nodeOverlapsBoundary($embeddedBoundary,$boundary))
                    {                      
                        $lastIndex = $nodeIndex;
                    }
                }

                //validate boundarues
                $boundaryLast = digi_pdf_to_html::returnBoundary([$lastIndex]); 
                $boundaryNext = digi_pdf_to_html::returnBoundary([$nextIndex]); 
                if($boundaryLast['maxLeft'] > $boundaryNext['maxLeft'] )    { continue; } //a previous node cannot exceed the next maxLeft value
       
                if(!digi_pdf_to_html::textNodesAreMergable($obj['nodes'][$lastIndex],$obj['nodes'][$nextIndex]) ) { continue ; }
                digi_pdf_to_html::mergeNodes($lastIndex,$nextIndex); 
                $this->execute($obj);
                return;
            }
        }

    }

    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    private function gatherVerticalTree($textNodes )
    {
        $objTree = [];
        
        //-----------------------
        /* 
            step1: collect all nodes with the same top value ( within margin set in  $this->marginTop  )
            a collection must have at least 2 nodes (or more) with the same top value (else it is not consoderd a column layout)

            example output: 
            Nodes 5 & 6 have same top value, with node 5 having nodes below it, hence method gatherVerticalTree(). 
            To see if these nodes belong to each other horizontally gatherHorizontalTree() will do that next.
            Purpose is to match the most bottom node within a tree to the first one of the next column

            [5] => Array
            (
                [nodes] =>  Array( [0] => 5, [1] => 11, [2] => 12 )
                [top] =>    265
                [left] =>   123
            )

            [6] => Array
            (
                [nodes] => Array    ( [0] => 6 )
                [top] => 265
                [left] => 521
            )

        */
        //-----------------------
        $arrayTopCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"top",$this->marginTop);        //same top value with their indexes
        $arrayTopCollection =    array_filter($arrayTopCollection, function($value) {return sizeof($value) > 1;});  //filter by 2 nodes or more (else not considered a column layout fro given top value)

        foreach ($arrayTopCollection as $top => $indexes) 
        {
                //----------------------------------
                //step2: sort each top selecion by left ASC; in the code the previous column will analyse the next one
                $topNodes =     digi_pdf_to_html::returnNodesFromIndexes($indexes);
                $topNodes  =    digi_pdf_to_html::sortNodesByProperty($topNodes,"left");

                //----------------------------------
                //step3: get the same left-nodes for each top-node
                foreach ($topNodes as $topIndex => $topProperties) 
                {
                    
                    //A) the top node will be the first node for the left-collection

                    if(!isset($objTree[$topIndex]))  
                    { 
                        $objTree[$topIndex]=[]; 
                        $objTree[$topIndex]['nodes']=    [$topIndex];
                        $objTree[$topIndex]['top']=      $top; //note: use $top and not $topProperties['top'] as we are using the grouped value of top, which may differ (slightly) from the actual top
                        $objTree[$topIndex]['left']=     $topProperties['left'];
                    } 
                    
                    $topLeft = $topProperties['left'];
                    foreach ($textNodes as $index => $properties) 
                    {
                        if($topIndex == $index)                                                         { continue; }   //aleady set in A
                        if($properties['top'] <= ($topProperties['top']+$this->marginTop) )             { continue; }   //the top node only needs to look to top-values higher then itself (so visually lower-located on the pagae)
                        if( abs($topProperties['left'] - $properties['left']) > $this->marginLeft   )   { continue; }   //the allowed margin exceed the value given in $this->marginLeft


                        $currentColumnBoundary = digi_pdf_to_html::returnBoundary( $objTree[$topIndex]['nodes'] ); 
                        if( abs($currentColumnBoundary['maxTop'] - $properties['top']) > $this->maxSectionYSeparator )   { break; }   //to much space between a next node and the last. Since $textNodes is sorted by top ASC, there is no need to continue, so a break can be performed
                        $objTree[$topIndex]['nodes'][]=$index;

                    }

                }
        }

        //sort by top ASC, then by left ASC (to be sure about the ordering)
        uasort($objTree, function($a, $b) {if ($a['top'] == $b['top']) {if ($a['left'] == $b['left']) { return 0; }  return ($a['left'] < $b['left']) ? -1 : 1; } return ($a['top'] < $b['top']) ? -1 : 1; });

        return $objTree;

    }
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    
    private function gatherHorizontalTree( $objTree ) //note $objTree is sorted by top ASC, then by left ASC (to be sure about the ordering)
    {
            $objGroups =    [];

            //-----------------------
            /*
                for example: grouping 2 columns with same top-value but seperated too much (exceeds $this->maxTextColumnSeparator)
    
                ------  -------             -------     -------
                ------  --- ---             --- ---     --- --- 
                -- ---  -------             ------      -------
            
                within a group take the
                    - first array, and its last-key (lowest node)
                    - next array and its first-key (highest node)
                    - always start witht the first array, basically the sequence goes from left -> right
                

                for example:
                - node 19 should merge with 20
                - node 17 will merge with 18. note that node 22 will merge with 57 will be done next time. 

                [0] => Array
                        (
                            [0] => Array(19)
                            [1] => Array(20,21)
                        )

                [1] => Array
                        (
                            [0] => Array(17)
                            [1] => Array(18,22)
                            [2] => Array(57)
                        )

            */
            //-----------------------
        
            $arrayKeys =    array_keys($objTree);  //keys of all top-nodes
            $len =          sizeof($arrayKeys);
            $groupId =      0;

            for( $n=0; $n < $len; $n++ )
            {
                $index =        $arrayKeys[$n];
                $top   =        $objTree[$index]['top'];
                $left  =        $objTree[$index]['left'];
                $indexes =      $objTree[$index]['nodes'];
                $boundary=      digi_pdf_to_html::returnBoundary( $indexes );  

                if(isset($arrayKeys[$n+1]))
                {
                    $index2 =        $arrayKeys[$n+1];
                    $top2   =        $objTree[$index2]['top'];
                    $left2   =       $objTree[$index2]['left'];
                    $indexes2 =      $objTree[$index2]['nodes'];
                    $boundary2=      digi_pdf_to_html::returnBoundary( $indexes2 );  
                    $diff =          abs($boundary2['left'] - $boundary['maxLeft']); 

                    if($top2 == $top)
                    {
                        if($diff <= $this->maxTextColumnSeparator )
                        {
                            if( !isset($objGroups[$groupId])  )             {  $objGroups[$groupId]=[]; }     
                            if(!in_array($indexes,$objGroups[$groupId]))    {  $objGroups[$groupId][] = $indexes; }
                            if(!in_array($indexes2,$objGroups[$groupId]))   {  $objGroups[$groupId][] = $indexes2; }      
                        } 
                        else
                        {
                            $groupId +=1 ;
                        }

                    } else { $groupId +=1 ;}

                }

            }

            //reindex keys
            $objGroups = array_values($objGroups);

            return $objGroups;
    }
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################
    //######################################################################################################

}

?>