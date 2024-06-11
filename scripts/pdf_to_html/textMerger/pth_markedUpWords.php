<?php
declare(strict_types=1);

/*
    words that were not merged yet due to:
    - having markup 
    - words start with markup (bold, italic etc)
    - same font size
    - same top

            ##title###  ---------------  ----- ---
    --- --------- - ---- -- ---- - -- - - -- -----
    ---------- - -- - - - - - - - - - -- - - - - -



*/

class pth_markedUpWords
{    
    private $marginTop= 3;
    
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
        $textNodes =                digi_pdf_to_html::returnProperties("tag","text",false);  
        $arrayTopCollection =       digi_pdf_to_html::collectPropertyValues($textNodes,"top",$this->marginTop);

        foreach ($arrayTopCollection as $top => $indexes) 
        {
            if(sizeof($indexes)<=1){ continue; }
            $nodes = digi_pdf_to_html::returnNodesFromIndexes($indexes);

            foreach ($nodes as $index1 => $property1) 
            {
                foreach ($nodes as $index2 => $property2) 
                {
                    if($index1 == $index2) {continue;}
                    $boundary1 = digi_pdf_to_html::returnBoundary([$index1]);
                    $boundary2 = digi_pdf_to_html::returnBoundary([$index2]);
                    if(!digi_pdf_to_html::nodeWithinBoundary($boundary1, $boundary2)) { continue; } 

                    $prop1 = $property1;
                    $prop1['content']=strip_tags($prop1['content']);
                    if(!digi_pdf_to_html::textNodesAreMergable($prop1,$property2) ) { continue ; }

                    digi_pdf_to_html::mergeNodes($index1,$index2); 
                    $this->execute($obj);
                    return;
                }

            }
        }
    }
    //#####################################################################
  

}

?>