<?php
declare(strict_types=1);
/*
    Remove odd characters or content that must be removed
*/

class pth_identationCorrections
{    

    private $margin = 3;
    private $maxIndentation =      15;
    private $maxTextYSeparator =   8;

    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();
        //-------------------------------
        $this->cleanup($obj);       
    }
    
    //#####################################################################
    private function cleanup(&$obj)
    {
        $textNodes =              digi_pdf_to_html::returnProperties("tag","text",false);    
        $arrayLeftCollection =    digi_pdf_to_html::collectPropertyValues($textNodes,"left",$this->margin);

        foreach ($arrayLeftCollection as $leftVal => $indexes) 
        {
            $boundary   =       digi_pdf_to_html::returnBoundary($indexes);
            $offset =           $leftVal + 1;
            $final  =           $leftVal + $this->maxIndentation;
            $arrIndents =       $this->returnRange($arrayLeftCollection,$offset,$final);
            if(sizeof($arrIndents)==0) { continue; }

            foreach ($arrIndents as $leftIndent => $indentIndexes) 
            {
                $len =  sizeof($indentIndexes);
                for($n=0;$n<$len;$n++)
                {
                    $indentBoundary =  digi_pdf_to_html::returnBoundary([$indentIndexes[$n]]); //$obj['nodes'][$indentIndexes[$n]];
                    if(!digi_pdf_to_html::nodeWithinBoundary($indentBoundary,$boundary)) { continue; }

                    $len2 =  sizeof($indexes);
                    for($i=0;$i<$len2;$i++)
                    {
                        if(!isset($indexes[$i+1])) { break; }
                        $index =    $indexes[$i];
                        $index2 =   $indexes[$i+1];

                        $leftBoundary =  digi_pdf_to_html::returnBoundary([$index]);
                        $leftBoundary2 = digi_pdf_to_html::returnBoundary([$index2]);

                        if( abs($leftBoundary['maxTop'] - $indentBoundary['top']) > $this->maxTextYSeparator  )     { continue; }
                        if( abs($leftBoundary2['top'] - $indentBoundary['maxTop']) > $this->maxTextYSeparator  )    { continue; }

                        if($indentBoundary['top'] > $leftBoundary['top'] && $indentBoundary['top'] < $leftBoundary2['top']  )
                        {
                            $obj['nodes'][$indentIndexes[$n]]['left'] =     $leftVal;
                            $obj['nodes'][$indentIndexes[$n]]['width'] +=   abs($leftIndent - $leftVal); //artificially preserve the right alignment by expanding the width
                        }

                    }  
                }
            } 
        }

    }

    //#####################################################################
    private function  returnRange($data, $start, $end) 
    {
       $filtered_data = [];
       foreach ($data as $key => $values) {
           if ($key >= $start && $key <= $end) {
               $filtered_data[$key] = $values;
           }
       }
       return $filtered_data;
   }
   //#####################################################################


}

?>