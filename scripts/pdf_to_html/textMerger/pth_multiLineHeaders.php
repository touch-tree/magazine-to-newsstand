<?php
declare(strict_types=1);
/*
    - merges text columns assumed header
    - must have same font-type
    - longest line is always the top one

     ##########################################################################################
     SCENARIO:

        -----------------      
        -----------------      
        -----------------       
        -----------------     
        ----------
        -----------------       
        ----------

        TITLE XX YY XX
        FF YY

        -----------------
        -------
        -----------------
        ------
        -----------------

*/


class pth_multiLineHeaders
{    
 
    private  $marginLeft = 10; 
    private  $marginTop =  10; //margin for top-value; 
    
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
        $textNodes =        digi_pdf_to_html::returnProperties("tag","text",false); 
        
        foreach ($textNodes as $index => $properties) 
        {
            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            
            //the next line (lower located)
            foreach ($textNodes as $index2 => $properties2) 
            {
                if($index == $index2)                                                           { continue; }
                if($properties['fontId'] <> $properties2['fontId'])                             { continue; }
                if(sys::length($properties2['content']) > sys::length($properties['content']) ) { continue; }

                $boundary2 = digi_pdf_to_html::returnBoundary([$index2]);

                if($boundary['maxTop'] > $boundary2['top'])             { continue; }
                $diffY = $boundary2['top'] - $boundary['maxTop'];
                $diffX = abs($boundary['left'] - $boundary2['left']);
                if($diffY > $this->marginTop )                          {continue; }
                if($diffX > $this->marginLeft )                         {continue; }
               
                digi_pdf_to_html::mergeNodes($index,$index2); 
                $this->execute($obj);
                return;
            }

        }

    

    }

    //#####################################################################
   
   


}

?>