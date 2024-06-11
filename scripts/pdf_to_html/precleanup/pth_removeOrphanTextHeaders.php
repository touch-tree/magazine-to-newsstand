<?php
declare(strict_types=1);
/*
    Remove floating header texts usually near the header of the page
*/

class pth_removeOrphanTextHeaders
{    
    
    private $maxTextLength =      50;
    private $minTopSpacing =      100;

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
        $textNodes =    digi_pdf_to_html::returnProperties("tag","text");
     
        $arrayKeys =    array_keys( $textNodes);
        $len =          sizeof($arrayKeys);
        for($n=0;$n<$len;$n++)
        {
            if(!isset($arrayKeys[$n+1]))                                    { break; }

            $index =        $arrayKeys[$n];
            $properties =   $textNodes[$index];
            $boundary =     digi_pdf_to_html::returnBoundary([$index]);

            $indexNext =        $arrayKeys[$n+1];
            $propertiesNext =   $textNodes[$indexNext];
            
            $indexPrev=         null;
            $propertiesPrev=    null; 

            if(isset($arrayKeys[$n-1]))
            {
                $indexPrev =        $arrayKeys[$n-1];
                $propertiesPrev =   $textNodes[$indexPrev];        
            }     
            
            if(sys::length($properties['content']) > $this->maxTextLength)  {continue;}
            
            $diff = $propertiesNext['top'] - $boundary['maxTop'];

            if($diff > $this->minTopSpacing )                              
            {
                         
                $doDelete = false;
                if(isset($indexPrev))
                {
                    $boundaryPrev =     digi_pdf_to_html::returnBoundary([$indexPrev]);
                    $diff =             $properties['top'] - $boundaryPrev['maxTop'];
                    if($diff > $this->minTopSpacing ) {$doDelete = true; }
                } else { $doDelete = true; }

                if($doDelete)
                {
                    digi_pdf_to_html::removeIndex($index);
                    $this->cleanup($obj);
                    return;     
                }
            }        
        }

   
    }

    //#####################################################################


}

?>