<?php
declare(strict_types=1);
/*
    Remove anything below a certain top-value
*/

class pth_removeHeader
{    
    private $maxTopMargin = 60;
    
    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        //-------------------------------
        $this->cleanup($obj);       
    }
    
    //#####################################################################
    private function cleanup(&$obj)
    {
        foreach( $obj['nodes'] as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            if($boundary['maxTop'] > $this->maxTopMargin ) { continue; }  
            digi_pdf_to_html::removeIndex($index);
            $this->cleanup($obj);
            return;
        }
    }

    //#####################################################################


}

?>