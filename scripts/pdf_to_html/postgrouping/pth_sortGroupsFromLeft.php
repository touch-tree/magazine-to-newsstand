<?php
declare(strict_types=1);



class pth_sortGroupsFromLeft
{    
   
    private $leftLevel = 20;

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
        

    }

     //#####################################################################


   
   


}

?>