<?php
declare(strict_types=1);
/*
   a scentence that belong belongs to a block above it, but has no matching left or right alignment. (for example due to an image layout)

   --- ------- ----------
   --------- --- ---- ---
   --- -- ------- ---- --
   ----- -- -- ----- ----
[ IMG ] ----- ------.


*/

class pth_trailingSentence
{    

    private $maxTextYSeparator =        8;
    private $minBlockHeight =           140;
    
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
        $textNodes =  digi_pdf_to_html::returnProperties("tag","text",false); 
        foreach( $textNodes as $index => $properties) 
        { 

            $boundary = digi_pdf_to_html::returnBoundary([$index]);
            if($boundary['height'] < $this->minBlockHeight ) { continue; }

            foreach( $textNodes as $index2 => $properties2) 
            { 
                $boundary2 = digi_pdf_to_html::returnBoundary([$index2]);

                if($boundary2['left'] < $boundary['left'] )         { continue; }
                if($boundary2['maxLeft'] > $boundary['maxLeft'] )   { continue; }
                $diff = abs($properties2['top'] - $boundary['maxTop']);
                if($diff > $this->maxTextYSeparator) { continue; }

                digi_pdf_to_html::mergeNodes($index,$index2); 
                $this->execute($obj);
                return;
            } 
        }
    }
    //###############################################################
    
   

}

?>