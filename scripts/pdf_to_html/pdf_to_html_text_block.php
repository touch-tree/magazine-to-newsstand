<?php

class pdf_to_html_text_block
{
    static private  $arrayBlocks =                  [];
    static private  $maxTextYSeparator =            5; //top position 

    //#####################################################################
    static public function process($page)
    {	
            
        $obj = digi_pdf_to_html::$arrayPages[$page]['content'];
        $len = sizeof( $obj );  

        $arrayGroupedIndex = []; 

        $lastTop =      null;
        $lastLeft =     null;
        $lastHeight =   null;

        /*
        for($n=0; $n < $len; $n++)
        {
            $doReset = false;
            if(isset($lastLeft))
            {
                if($lastLeft == $obj['left'][$n])
                {
                    $topDiff = abs($obj['top'][$n] - ($lastTop + $lastHeight)) ;
                    if($topDiff <= self::$maxTextYSeparator )
                    {
                        $arrayGroupedIndex[]=$n;
                    }
                    else
                    {
                        $doReset = true;      
                    }
                }
                else
                {
                    $doReset = true;        
                }
            }

            if($doReset)
            {
                //print_r($arrayGroupedIndex);
                
                $lastTop =      null;
                $lastLeft =     null;
                $lastHeight =   null;  
                $arrayGroupedIndex = [];        
            }

            $lastLeft =     $obj['left'][$n];
            $lastTop =      $obj['top'][$n];
            $lastHeight =   $obj['top'][$n];
        }
        */
    }
    //#####################################################################

}

?>