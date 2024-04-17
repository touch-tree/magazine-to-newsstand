<?php

class pdf_to_html_default
{
    //#####################################################################
    static public function process($page)
    {	
            
            //--------------
            //default sorting
            //sort array by property 'top' ASC and then 'left' ASC 
<<<<<<< HEAD
            usort(digi_pdf_to_html::$arrayPages[$page]['content'], function ($item1, $item2) 
=======
            usort(digi_pdf_to_html::$arrayPages[$page]['content'], function ($item1, $item2)
>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be
            {
                if ($item1['top'] == $item2['top']) 
                {
                    return $item1['left'] <=> $item2['left'];
                }

                return $item1['top'] <=> $item2['top'];
            });

    }
    //#####################################################################

}

?>