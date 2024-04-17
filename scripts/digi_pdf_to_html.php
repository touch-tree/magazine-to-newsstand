<?php
declare(strict_types=1);
//######################################

/*
    
    Using Poppler functions
    $pdfPath =  "D:/tempPdfs/italie magazine 2021_2_iPAD.pdf";
    digi_pdf_to_html::process($pdfPath,9,10);

    //collect html output per page:

    //-------

    foreach (digi_pdf_to_html::$arrayPages as $page => $value) 
    {
        $html  = digi_pdf_to_html::returnPageHtml($page);
    }


*/
//######################################
class digi_pdf_to_html
{

    static public   $arrayPages =           [];
    static public   $processFolder=         null;

    static private  $isInitiated=           false;
    static private  $baseCommand=     		null;
    static private  $filePrefix=            "content";


    //###########################################
    static private function init()
    {	
        if(self::$isInitiated) { return ;}
        self::$isInitiated=true;
        self::$baseCommand = dirname(__DIR__)."/bin/pdftohtml";

        //add extra parsing folder with their own classes
        $dir = __DIR__."/pdf_to_html/";
        set_include_path(implode(PATH_SEPARATOR, 
        array(get_include_path(),$dir)));
        spl_autoload_register();   

    }

    //###########################################
    static public function process($pdfPath,$pageNumberStart = null, $pageNumberFinal = null)
    {	
            self:: init();
            if(!is_file($pdfPath)) { sys::error("pdf-path '".$pdfPath."' is invalid "); } 
           
            //---------
            //setup temporary folder
            self::$processFolder = files::standardizePath(settings::server()['tempFolder'] . "/".sys::databaseDir()."/".md5($pdfPath)."/"); 
            if(is_dir(self::$processFolder)) { files::removeFolder(self::$processFolder); }
            files::createDir(self::$processFolder);

            //---------
            //the html content (note must be xml, as this version contains image x-y-location data)
            $params = array(
                "xml" =>                 [null,null],
                "fontfullname" =>        [null,null],
                "p" =>                   [null,null],
                "c" =>                   [null,null]
                );
                

            if(isset($pageNumberStart)) { $params['f'] = [$pageNumberStart," "];}
            if(isset($pageNumberFinal)) { $params['l'] = [$pageNumberFinal," "];}

            $command =  self::$baseCommand.shell::extractParams($params).' '.escapeshellarg($pdfPath).' '.self::$processFolder."/".self::$filePrefix;
            $out = shell::command($command,self::$processFolder);

            //-------
            //collect files-data
            self::collectContent();

    }

    //###########################################
    static private function collectContent()
    {	
        $contentFile = self::$filePrefix.".xml";
        $contentPath = files::standardizePath(self::$processFolder."/".$contentFile);
        if(!is_file($contentPath)) { sys::error("content-path '".$contentPath."' is invalid "); }
        $dom = new html_parser();
        $xml = files::fileGetContents($contentPath);
        $dom->setFullHtml($xml);
        $pages =    $dom->tagName('page');
        $loop =     $pages->length;

        self:: $arrayPages = [];
   
        for($n=0; $n < $loop; $n++)
        {
         
            $pageNumber =  $dom->getAttribute($pages[$n],"number");
            self::$arrayPages[$pageNumber]=[];
            self::$arrayPages[$pageNumber]['meta']=     [];
            self::$arrayPages[$pageNumber]['content']=  [];
            self::$arrayPages[$pageNumber]['meta']['pageWidth']=    $dom->getAttribute($pages[$n],"width"); 
            self::$arrayPages[$pageNumber]['meta']['pageHeight']=   $dom->getAttribute($pages[$n],"height"); 

            $nodes = $dom->tagName('*', $pages[$n] );
            $loop2 =     $nodes->length;
            for($i=0; $i < $loop2; $i++)
            {
                
                $tag = $dom->returnNodeName($nodes[$i]);
                if(!in_array($tag,["text","image"]))                            {continue;}
                if(!$dom->hasAttribute($nodes[$i],"top"))                       {continue;}
                if(!$dom->hasAttribute($nodes[$i],"left"))                      {continue;}
                if(!$dom->hasAttribute($nodes[$i],"height"))                    {continue;}
                if(!$dom->hasAttribute($nodes[$i],"width"))                     {continue;}
                if( $tag === "text" && sys::length($nodes[$i]->textContent)==0) {continue;}
                $top =      $dom->getAttribute($nodes[$i],"top");
                $left =     $dom->getAttribute($nodes[$i],"left");
                $height =   $dom->getAttribute($nodes[$i],"height");
                $width =    $dom->getAttribute($nodes[$i],"width");
                if( $top < 0 or $left < 0 or $height<=0 or $width <= 0 )        {continue;}

                $content = null;
                $fontId  = null;

                if( $tag === "image" )  
                { 
                    $src =  $dom->getAttribute($nodes[$i],"src"); 
                    if(is_file($src)) {$content = basename($src);} 
                }
                else 
                { 
                    $content =  $dom->innerHTML($nodes[$i]); 
                    if($dom->hasAttribute($nodes[$i],"font"))  { $fontId = $dom->getAttribute($nodes[$i],"font"); }   
                }

                $arr = [];
                $arr['tag'] =           $tag;
                $arr['top'] =           $top;
                $arr['left'] =          $left;
                $arr['height'] =        $height;
                $arr['width'] =         $width;
                $arr['content'] =       $content;
                $arr['fontId'] =        $fontId;
                $arr['groupNumber'] =   0;
                $arr['isDeletable'] =   false;
                self::$arrayPages[$pageNumber]['content'][]=$arr;

            }


        }

        //--------------
        //sort by page number (asc)
        ksort(self::$arrayPages);

        //-------------
        //add font information to the array
        self::$arrayPages =  ['fonts' => []] + self::$arrayPages; 
        $fonts =    $dom->tagName('fontspec');
        $loop =     $fonts->length;
        for($n=0; $n < $loop; $n++)
        {
            $fontId = $dom->getAttribute($fonts[$n],"id");
            self::$arrayPages['fonts'][$fontId] = [];
            self::$arrayPages['fonts'][$fontId]['size'] = $dom->getAttribute($fonts[$n],"size");
            self::$arrayPages['fonts'][$fontId]['family'] = $dom->getAttribute($fonts[$n],"family"); 
            self::$arrayPages['fonts'][$fontId]['color'] = $dom->getAttribute($fonts[$n],"color");         
        }

     
    }


    static public function returnNewGroupNumber($page):string
    {
        return max(self::$arrayPages[$page]['groupNumber']) + 1;
    }

    //###########################################
    //###########################################
    //###########################################
    //HTML BUILDER!!!!!
    //###########################################
    //###########################################
    //###########################################

    static public function returnPageHtml($page):string
    {
        if(sys::posInt($page)==0)               { return null ; }
        if(!isset(self::$arrayPages[$page]))    { return null ; }   
        self::setRulesLogic($page);
        return self::returnFinalHtml($page);
    }

    //###########################################

    static private function returnFinalHtml($page):string
    { 
            $arrayHtmlBlocks = [];
            $obj = self::$arrayPages[$page]['content'];

            $len =          sizeof( $obj ); 
            $currentGroup = 0; 
            $content =      "";

            for($n=0; $n < $len; $n++)
            {
                
				if( $obj[$n]['isDeletable'] ) { continue; }
				                
                //tag management
                if($obj[$n]['tag'] === "text")
                {
                    $content .= $obj[$n]['content'];
                }
                else
                {
                    if(sys::length($content)>0){$arrayHtmlBlocks[] = $content; }
                    $img = self::$processFolder."/".$obj[$n]['content'];
                    $blob = files::fileGetContents($img);
                    $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
                    $src = images::base64FromBlob($blob,$ext);
                    $arrayHtmlBlocks[] =  "<div><img id=\"".basename($img)."\" src='".$src."'  /></div>";
                    $content = "";
                }  
            }

            if(sys::length($content)>0){$arrayHtmlBlocks[] = $content; }
        
            return implode("<hr>",$arrayHtmlBlocks);
        
    }

    //########################################################

    static private function setRulesLogic($page)
    {          
        pdf_to_html_default::process($page); 
        pdf_to_html_filter_image_dimensions::process($page); 
        pdf_to_html_text_block::process($page); 

        //pdf_to_html_logic1:process($page); 
        //pdf_to_html_logic2:process($page); 
    }

    //###########################################

    
}
?>