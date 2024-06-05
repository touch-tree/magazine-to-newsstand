<?php
declare(strict_types=1);

/*
    - grouo text-nodes with a group-boundary, when the text is atop of the boundary
    - sorting from lowest to higher in document
*/

class pth_ungroupedTextHeaderAboveUngroupedText
{    
   
    private $marginY =              25;
    private $marginX =              10;
    private $maxHeaderCharsLen =    100;

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
        
        $textNodes =         digi_pdf_to_html::returnProperties("tag","text",false);
        $textNodes =         digi_pdf_to_html::sortNodesByProperty($textNodes,"top",false);

        foreach ($textNodes as $index => $properties) 
        {
          
            $boundary =         digi_pdf_to_html::returnBoundary([$index]);
   
            foreach ($textNodes as $index2 => $properties2) 
            {
                $boundary2 =        digi_pdf_to_html::returnBoundary([$index2]); //must be a higher located node
                if($index == $index2)                    { continue;}
                if($boundary2['top'] > $boundary['top']) { continue;}

                if( abs($boundary['top'] - $boundary2['maxTop']) > $this->marginY  )    { continue; }
                if( abs($boundary['left'] - $boundary2['left']) > $this->marginX  )     { continue; }
                if( sys::length($properties2['content'])>$this->maxHeaderCharsLen)      { continue; }

                $grouped = digi_pdf_to_html::groupNodes([$index,$index2]);
                if($grouped)
                {
                    $this->execute($obj);  
                    return;
                }
            }

        }


    }

     //#####################################################################


   
   


}

?>