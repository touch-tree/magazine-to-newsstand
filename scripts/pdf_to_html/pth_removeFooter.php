<?php
declare(strict_types=1);

class pth_removeFooter
{
    
    private $maxBottomMargin = 65;

    public function __construct(&$obj)
    {
        
        //-----------------------------------------------
        //force sorting
        digi_pdf_to_html::sortByTopThenLeftAsc($obj);
        
        $pageHeight = $obj['meta']['pageHeight'];
        $maxTop = $pageHeight - $this->maxBottomMargin;

        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['top'] < $maxTop )   {continue;}  
            unset($obj['content'][$index]);
        } 

        
        $obj['content'] = array_values($obj['content']); //re-index data object
        

    }

}

?>