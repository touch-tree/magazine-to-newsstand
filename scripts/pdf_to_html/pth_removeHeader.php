<?php
declare(strict_types=1);

class pth_removeHeader
{
    
    private $maxTopMargin = 60;
    
    public function __construct(&$obj)
    {
        
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
      
        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['top'] > $this->maxTopMargin )     { continue; }  
            unset($obj['content'][$index]);
        } 
       
        $obj['content'] = array_values($obj['content']);//re-index data object
        

    }

}

?>