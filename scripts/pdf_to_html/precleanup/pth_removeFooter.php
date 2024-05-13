<?php
declare(strict_types=1);
/*
    Remove anything above a certain top-value
*/

class pth_removeFooter
{    
    private $maxBottomMargin = 65;
    
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
        $pageHeight =   $obj['meta']['pageHeight'];
        $maxTop =       $pageHeight - $this->maxBottomMargin;
        
        foreach( $obj['nodes'] as $index => $properties) 
        {
            if($properties['top'] < $maxTop )   { continue; }  
            digi_pdf_to_html::removeIndex($index);
            $this->cleanup($obj);
            return;
        }
    }

    //#####################################################################


}

?>