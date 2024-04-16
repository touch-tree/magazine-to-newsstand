<?php

class pdf_to_html_filter_image_dimensions
{
    //#####################################################################
    //assume streched vertical and horizontal images are part of local design/layout and not relevant for html output
    //#####################################################################
    static public function process($page)
    {	
            

            $obj = digi_pdf_to_html::$arrayPages[$page];
            $len = sizeof( $obj['top']);   
            for($n=0; $n < $len; $n++)
            {
                if( $obj['tag'][$n] !== "image" ) { continue; }

                $img = digi_pdf_to_html::$processFolder."/".$obj['content'][$n];
                images::detectImageDimensions($img);

                print_r(images::$settings);
                echo "xxx"

            }
              


    }
    //#####################################################################

}

?>