<?php
declare(strict_types=1);
<<<<<<< HEAD
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
=======

>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be
class digi_pdf_to_html
{
    static public array $arrayPages = [];
    static public ?string $processFolder = null;
    static private bool $isInitiated = false;
    static private ?string $baseCommand = null;
    static private string $filePrefix = 'content';

<<<<<<< HEAD
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
=======
    /**
     * Start the class.
     *
     * @return void
     */
    private static function init(): void
    {
        if (self::$isInitiated) {
            return;
        }

        self::$isInitiated = true;
        self::$baseCommand = dirname(__DIR__) . '/bin/pdftohtml';

        // Add extra parsing folder with their own classes

        $dir = __DIR__ . '/pdf_to_html/';

        set_include_path(implode(PATH_SEPARATOR, [get_include_path(), $dir]));
        spl_autoload_register();
    }

    /**
     * Process a PDF.
     *
     * @param string $pdfPath
     * @param int|null $pageNumberStart
     * @param int|null $pageNumberFinal
     * @return void
     */
    public static function process(string $pdfPath, int $pageNumberStart = null, int $pageNumberFinal = null): void
    {
        self::init();

        if (!is_file($pdfPath)) {
            sys::error('pdf-path is invalid: ' . $pdfPath);
        }

        // Setup temporary folder

        self::$processFolder = files::standardizePath(settings::server()['tempFolder'] . '/' . sys::databaseDir() . '/' . md5($pdfPath) . '/');

        // Remove process folder contents

        if (is_dir(self::$processFolder)) {
            files::removeFolder(self::$processFolder);
        }

        files::createDir(self::$processFolder);

        // The HTML content (note must be XML, as this version contains image x-y-location data)

        $params = array(
            'xml' => [null, null],
            'fontfullname' => [null, null],
            'p' => [null, null],
            'c' => [null, null]
        );

        if (isset($pageNumberStart)) {
            $params['f'] = [$pageNumberStart, ' '];
        }

        if (isset($pageNumberFinal)) {
            $params['l'] = [$pageNumberFinal, ' '];
        }

        $command = self::$baseCommand . shell::extractParams($params) . ' ' . escapeshellarg($pdfPath) . ' ' . self::$processFolder . '/' . self::$filePrefix;

        // Create an XML file with the provided arguments and PDF pages

        shell::command($command, self::$processFolder);

        self::collectContent();
    }

    /**
     * Get the content.
     *
     * @return void
     */
    private static function collectContent(): void
    {
        $path = files::standardizePath(self::$processFolder . '/' . self::$filePrefix . '.xml');

        if (!is_file($path)) {
            sys::error('content - path is invalid: ' . $path);
        }

        $dom = new html_parser();

        // Get the data from the XML

        $dom->setFullHtml(files::fileGetContents($path));

        foreach ($dom->tagName('page') as $page) {
            $pageNumber = $dom->getAttribute($page, 'number');

            self::$arrayPages[$pageNumber] = [
                'meta' => [
                    'pageWidth' => $dom->getAttribute($page, 'width'),
                    'pageHeight' => $dom->getAttribute($page, 'height')
                ],
                'content' => []
            ];

            foreach ($dom->tagName('*', $page) as $node) {
                $tag = $dom->returnNodeName($node);

                if (!in_array($tag, ['text', 'image'])) {
                    continue;
                }

                // Validate attributes

                if (
                    !$dom->hasAttribute($node, 'top') ||
                    !$dom->hasAttribute($node, 'left') ||
                    !$dom->hasAttribute($node, 'height') ||
                    !$dom->hasAttribute($node, 'width')
                ) {
                    continue;
                }

                $top = $dom->getAttribute($node, 'top');
                $left = $dom->getAttribute($node, 'left');
                $height = $dom->getAttribute($node, 'height');
                $width = $dom->getAttribute($node, 'width');

                // Check for non-dimensional elements and ignore them

                if ($top < 0 || $left < 0 || $height <= 0 || $width <= 0) {
                    continue;
                }
>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be

                $content = null;
                $fontId  = null;

<<<<<<< HEAD
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

=======
                if ($tag === 'image') {
                    $src = $dom->getAttribute($node, 'src');
                    if (is_file($src)) {
                        $content = basename($src);
                    }
                } else {
                    $content = $dom->innerHTML($node);
                    if ($dom->hasAttribute($node, 'font')) {
                        $fontId = $dom->getAttribute($node, 'font');
                    }
                }

                self::$arrayPages[$pageNumber]['content'][] = [
                    'tag' => $tag,
                    'top' => $top,
                    'left' => $left,
                    'height' => $height,
                    'width' => $width,
                    'content' => $content,
                    'fontId' => $fontId,
                    'fonts' => [],
                    'groupNumber' => 0,
                    'isDeletable' => false
                ];
>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be
            }


        }

        // Sort by page number (asc)

        ksort(self::$arrayPages);

<<<<<<< HEAD
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
=======
        // Add font information to the array

        foreach ($dom->tagName('fontspec') as $font) {
            self::$arrayPages['fonts'][$dom->getAttribute($font, 'id')] = [
                'size' => $dom->getAttribute($font, 'size'),
                'family' => $dom->getAttribute($font, 'family'),
                'color' => $dom->getAttribute($font, 'color')
            ];
>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be
        }

     
    }


    static public function returnNewGroupNumber($page):string
    {
        return max(self::$arrayPages[$page]['groupNumber']) + 1;
    }

    /**
     * Get the new group number from a page.
     *
     * @param int $page
     * @return string
     */
    public static function getNewGroupNumber(int $page): string
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

<<<<<<< HEAD
    static public function returnPageHtml($page):string
    {
        if(sys::posInt($page)==0)               { return null ; }
        if(!isset(self::$arrayPages[$page]))    { return null ; }   
=======
    /**
     * Get the HTML of a page.
     *
     * @param int $page
     * @return string|null
     */
    public static function returnPageHtml(int $page): ?string
    {
        if (!isset(self::$arrayPages[$page]) || sys::posInt($page) === 0) {
            return null;
        }

>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be
        self::setRulesLogic($page);
        return self::returnFinalHtml($page);
    }

<<<<<<< HEAD
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
=======
    /**
     * Generate an HTML using the provided page and configuration.
     *
     * @param int $page
     * @return string
     */
    public static function returnFinalHtml(int $page): string
    {
        $blocks = [];
        $content = '';

        foreach (self::$arrayPages[$page]['content'] as $item) {
            if ($item['isDeletable']) {
                continue;
            }

            // Handle text and image tags

            if ($item['tag'] === 'text') {
                $content .= $item['content'];
            } else {
                if (sys::length($content) > 0) {
                    $blocks[] = $content;

                    // Reset content for new block

                    $content = '';
                }

                $img = self::$processFolder . '/' . $item['content'];

                // Prepare image element

                $blob = files::fileGetContents($img);
                $src = images::base64FromBlob($blob, strtolower(pathinfo($img, PATHINFO_EXTENSION)));

                $blocks[] = '<div><img id="' . basename($img) . '" src="' . $src . '" alt=""/></div>';
            }
        }

        if (sys::length($content) > 0) {
            $blocks[] = $content;
        }

        return implode('<hr>', $blocks);
    }

    /**
     * Here we implement our post-processing for the data before a HTML is generated from it.
     *
     * @param int $page
     * @return void
     */
    private static function setRulesLogic(int $page): void
    {
        pdf_to_html_default::process($page);
        pdf_to_html_filter_image_dimensions::process($page);
        pdf_to_html_text_block::process($page);
>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be

        // Add more processors here.
    }
<<<<<<< HEAD

    //###########################################

    
}
?>
=======
}
>>>>>>> ebf458deec16bb5820cdddb97914f204897ee7be
