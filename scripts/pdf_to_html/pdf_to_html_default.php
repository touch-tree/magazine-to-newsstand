<?php

class pdf_to_html_default
{
    static private  $arrayBlocks =              [];
    static private  $VerticalDividerQuantity =   30;

    //#####################################################################
    static public function process($page)
    {	
            
            //--------------
            //default grouping by top, and then left
            array_multisort
            (
                digi_pdf_to_html::$arrayPages[$page]['top'], SORT_ASC, 
                digi_pdf_to_html::$arrayPages[$page]['left'], SORT_ASC, 
                digi_pdf_to_html::$arrayPages[$page]['tag'], 
                digi_pdf_to_html::$arrayPages[$page]['height'], 
                digi_pdf_to_html::$arrayPages[$page]['width'], 
                digi_pdf_to_html::$arrayPages[$page]['content'],
                digi_pdf_to_html::$arrayPages[$page]['fontId'],
                digi_pdf_to_html::$arrayPages[$page]['groupNumber'],
                digi_pdf_to_html::$arrayPages[$page]['orderNumber'],
				digi_pdf_to_html::$arrayPages[$page]['isDeletable']
            );


        
            self::$arrayBlocks=[];
            $obj = digi_pdf_to_html::$arrayPages[$page];
            $groupNumber = 0;
            $orderNumber = 0;
            $len =         sizeof( $obj['top']);   

            $currentTag =      null;
            $currentTop =      0;

            for($n=0; $n < $len; $n++)
            {
                $tag =          $obj['tag'][$n];
                $top =          $obj['top'][$n];
                $isNewGroup =   false;

                if(!isset($currentTag))                                                         { $isNewGroup = true; }
                elseif($currentTag !== $obj['tag'][$n] )                                        { $isNewGroup = true; }  
                elseif( abs($obj['top'][$n] - $currentTop) > self::$VerticalDividerQuantity )   { $isNewGroup = true; }   
                
                if($isNewGroup)
                {
                    $groupNumber = max(digi_pdf_to_html::$arrayPages[$page]['groupNumber']) + 1;
                    $orderNumber = 0;
                }

                $currentTag = $obj['tag'][$n];
                $currentTop = $obj['top'][$n]; 
                $orderNumber += 1;
                digi_pdf_to_html::$arrayPages[$page]['groupNumber'][$n] = $groupNumber;
                digi_pdf_to_html::$arrayPages[$page]['orderNumber'][$n] = $orderNumber;
            }
    }
    //#####################################################################

}

?>