<?php
declare(strict_types=1);

/*
    - groups nodes into a natural format
    - creates values for groupSequenceNumber property
*/

class pth_relocateSingleCharacters
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
        $textNodes =        digi_pdf_to_html::returnProperties("tag","text");
        $objLangauge =      new language(); 
        
        
        
        foreach ($textNodes as $index => $properties) 
        {
            $text =                     $properties['content'];
            if(sys::length($text)<20)   { continue; }
          
            $languageCode =     $objLangauge->returnDetectedLanguage($text);  
            $obj['nodes'][$index]['content'] = $this->appendCharsToPrevWord($text,$languageCode);


        }
    }

     //#####################################################################
     private function appendCharsToPrevWord($str,$languageCode) 
     {
        $arrayChars = [];
        if($languageCode === "nl") { $arrayChars=["b","c","d","e","f","g","h","i","j","k","l","m","n","p","q","r","w","x","z"]; }
        if($languageCode === "en") { $arrayChars=["b","c","d","e","f","g","h","j","k","l","m","n","p","q","r","w","x","z"]; }
        if($languageCode === "fr") { $arrayChars=["b","c","d","e","f","g","h","j","k","l","m","n","p","q","r","w","x","z"]; }

        $words = explode(' ', $str);
        $len = sizeof($words);
        $out = [];
        for ($i = 0; $i <  $len; $i++) 
        {
            $word = $words[$i];
            $out[$i]= $word;

            if(isset($out[$i-1]) && strlen($word) == 1 && sys::isAlpha($word) && in_array($word,$arrayChars ) ) 
            {
                    $out[$i - 1] .= $word;
                    unset($out[$i]);
            }
        }
        return implode(' ', $out);
    }
    //####################################################################

   
   


}

?>