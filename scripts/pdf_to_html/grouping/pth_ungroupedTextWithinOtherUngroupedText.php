<?php
declare(strict_types=1);

/*
    - grouop text-nodes that are still ungrouped but fall WITHIN another group-boundary 
*/

class pth_ungroupedTextWithinOtherUngroupedText
{    
   
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
        
        
        $textNodes =        digi_pdf_to_html::returnProperties("tag","text",false);   //ungrouped texts!
        foreach ($textNodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            
            foreach ($textNodes as $index2 => $properties2) 
            {
                if($properties === $properties2) {continue;}
                if(digi_pdf_to_html::nodeWithinBoundary($properties2,$boundary))
                {
                    $grouped = digi_pdf_to_html::groupNodes([$index,$index2]);
                    if($grouped)
                    {
                        $this->execute($obj);  
                        return;
                    }
         
                    
                }
            }

        }
     
    }

     //#####################################################################


   
   


}

?>