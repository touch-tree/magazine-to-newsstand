<?php
declare(strict_types=1);
/*
    Remove anything below a certain top-value
*/

class pth_removeHeader
{    
    private $maxTopMargin = 60;
    
    public function __construct(&$obj)
    {
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        //-------------------------------
        $this->cleanup($obj);       
    }
    
    //#####################################################################
    private function cleanup(&$obj)
    {
        foreach( $obj['content'] as $index => $properties) 
        {
            if($properties['top'] > $this->maxTopMargin )     { continue; }  
            digi_pdf_to_html::removeIndex($obj,$index);
            $this->cleanup($obj);
            return;
        }
    }

    //#####################################################################


}

?>