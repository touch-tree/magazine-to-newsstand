<?php
declare(strict_types=1);



class pth_capitalStartLetter
{    

    private $fontSize = 50;

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
            
            $keys = array_keys($textNodes);
            $len  = sizeof($keys);
            for($n=0;$n<$len;$n++)
            {
                $index =        $keys[$n];
                $properties =   $obj['nodes'][$index];

                if(!isset($keys[$n+1]))                                                  { continue; }
                if( $properties['fontSize'] < $this->fontSize )                          { continue; }
                if(sys::length($properties['content']) > 1)                              { continue; }
                if(sys::strtoupper($properties['content']) !== $properties['content'])   { continue; }

                $index2 =        $keys[$n+1];
                $properties2 =   $obj['nodes'][$index2];
                $boundary =      digi_pdf_to_html::returnBoundary([$index2]);

                if(digi_pdf_to_html::nodeOverlapsBoundary($properties,$boundary) OR digi_pdf_to_html::nodeWithinBoundary($properties,$boundary) )
                {
                    //do not use digi_pdf_to_html::mergeNodes() because the capital letter dimension should not be taken in account when recreating a new dimension for the target node.
                    $obj['nodes'][$index2]['content'] = $properties['content'].$obj['nodes'][$index2]['content'] ;
                    digi_pdf_to_html::removeIndex($index);
                    $this->execute($obj);
                    return;
                }
            }
    }

     //#####################################################################


     //------------------------------------------------------------------------

    
   


}

?>