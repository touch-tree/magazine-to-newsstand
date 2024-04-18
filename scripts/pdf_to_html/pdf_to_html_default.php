<?php

class pdf_to_html_default
{
    //#####################################################################
    static public function process(&$obj)
    {	
            
            //--------------
            //default sorting
            //sort array by property 'top' ASC and then 'left' ASC 
            usort($obj['content'], function ($item1, $item2) 
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