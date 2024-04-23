<?php
declare(strict_types=1);

class digi_pdf_to_html
{
    static public array $arrayPages =       [];
    static public array $arrayFonts =       [];
 

    static public ?string $processFolder =  null;
    static private bool $isInitiated =      false;
    static private ?string $baseCommand =   null;
    static private string $filePrefix =     'content';

    //###################################################################################

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

    //##################################################################################
    
    public static function process(string $pdfPath, ?int $pageNumberStart = null, ?int $pageNumberFinal = null): void
    {
        self::init();

        self::$arrayPages =       [];
        self::$arrayFonts =       [];

        if (!is_file($pdfPath)) {
            sys::error('pdf-path is invalid: ' . $pdfPath);
        }

        //-----------------------------
        // Setup temporary folder
        self::$processFolder = files::standardizePath(settings::server()['tempFolder'] . '/' . sys::databaseDir() . '/' . md5($pdfPath) . '/');

        //--------------------------------
        // Manage temporary process folder to place content
        if (is_dir(self::$processFolder)) {
            files::removeFolder(self::$processFolder);
        }
        files::createDir(self::$processFolder);

        //--------------------------------
        // The HTML content and must be XML in the command, as this version also contains image x-y-location data.
        $params = array(
            'xml' => [null, null],
            'fontfullname' => [null, null],
            'p' => [null, null],
            'c' => [null, null]
        );

        if (isset($pageNumberStart)) { $params['f'] = [$pageNumberStart, ' ']; }
        if (isset($pageNumberFinal)) { $params['l'] = [$pageNumberFinal, ' ']; }

        $command = self::$baseCommand . shell::extractParams($params) . ' ' . escapeshellarg($pdfPath) . ' ' . self::$processFolder . '/' . self::$filePrefix;
        shell::command($command, self::$processFolder);
        self::collectContent();
    }

    //##################################################################################

    
    private static function collectContent(): void
    {
        $path = files::standardizePath(self::$processFolder . '/' . self::$filePrefix . '.xml');

        if (!is_file($path)) {  sys::error('content - path is invalid: ' . $path); }

        //---------------
        //parse the generated xml
        $dom = new html_parser();
        $dom->setFullHtml(files::fileGetContents($path));
        
    
        foreach ($dom->tagName('page') as $page) 
        {
            $pageNumber =       $dom->getAttribute($page, 'number');
            $pageWidth  =       $dom->getAttribute($page, 'width');
            $pageHeight  =      $dom->getAttribute($page, 'height');

            self::$arrayPages[$pageNumber] = [
                'meta' => [
                    'pageWidth' => $dom->getAttribute($page, 'width'),
                    'pageHeight' => $dom->getAttribute($page, 'height')
                ],
                'content' => []
            ];

            foreach ($dom->tagName('*', $page) as $node) 
            {
                $tag = $dom->returnNodeName($node);

                //---------------------
                //basic validation
                if (!in_array($tag, ['text', 'image'])) { continue; } 

                // Validate attributes
                $attributes = ['top', 'left', 'height', 'width'];
                foreach ($attributes as $attribute)  { if (!$dom->hasAttribute($node, $attribute)) {continue 2;}  }

                $top =      $dom->getAttribute($node, 'top');
                $left =     $dom->getAttribute($node, 'left');
                $height =   $dom->getAttribute($node, 'height');
                $width =    $dom->getAttribute($node, 'width');

                //---------------------
                //elements out of visual range
                if ($top < 0 || $left < 0 || $height <= 0 || $width <= 0)   { continue; }
                if ($top > $pageHeight || $left > $pageWidth )              { continue; }

                //---------------------
                //parse actual content
                $content =  null;
                $fontId =   null;

                if ($tag === 'image') 
                {
                    $src = $dom->getAttribute($node, 'src');
                    if (is_file($src)) { $content = basename($src);}
                } 
                else 
                {
                    if( sys::length($node->textContent) == 0) {continue;}
                    $content = $dom->innerHTML($node); //note: do not/never trim this for whitespace!
                    if ($dom->hasAttribute($node, 'font')) { $fontId = $dom->getAttribute($node, 'font');}
                }

                self::$arrayPages[$pageNumber]['content'][] = [
                    'tag' => $tag,
                    'top' => $top,
                    'left' => $left,
                    'height' => $height,
                    'width' => $width,
                    'content' => $content,
                    'fontId' => $fontId,
                    'groupNumber' => 0
                ];
            }
        }

        //----------------------------
        // Sort key (which is the page number) asc
        ksort(self::$arrayPages);

        //---------------------------
        // Collect font information self::$arrayFonts
        foreach ($dom->tagName('fontspec') as $font) 
        {
            self::$arrayFonts[$dom->getAttribute($font, 'id')] = [
                'size' => $dom->getAttribute($font, 'size'),
                'family' => $dom->getAttribute($font, 'family'),
                'color' => $dom->getAttribute($font, 'color')
            ];
        }

        
    }


    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //HELPER FUNCTIONS
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################

    //------------------------------------------
    //SORTING
    //sorts base-array self::$arrayPages[$page] by top-position (asc), and then left-position(asc).
    //content is handled (by default) from top-left to bottom-right
    static public function sortByTopThenLeftAsc(&$obj):void 
    {
        usort($obj['content'], function ($item1, $item2)  
        {
            if ($item1['top'] == $item2['top']) { return $item1['left'] <=> $item2['left']; }
            return $item1['top'] <=> $item2['top'];
        });
    }

    //-----------------------------------------
    //GROUPING CONTENT
    //obtain a new group-number within 1 page (self::$arrayPages[$page]). 
    //Note that the group-number does not care about any top- or left positioning. It is simply for grouping purposes.     
    public static function getNewGroupNumber(&$obj): int  
    {
        $groupNumbers = array_column($obj['content'], 'groupNumber');
        return max($groupNumbers) + 1;
    }

    //----------------------------------------
    //INDEX FILTERING
    //obtain index values, with it current properties from base-array self::$arrayPages[$page]
    //Note that the index-numbers themselves are preserved.
    static public function filterSelectedIndexes($obj, array $arrayIndexes):array 
    {
        $array =    $obj['content'];    
        $values =   [];
            
        foreach($arrayIndexes as $index) 
        {
                if(isset($array[$index])) { $values[$index] = $array[$index]; }
        }
        
        return $values;
    }

    
    //----------------------------------------
    //SORTING
    //sorts the base-array self::$arrayPages[$page]['content'] on a property value (asc or desc) . 
    //Note that the index-numbers themselves are preserved.
    static public function sortArrayByProperty(array $array, string $property, bool $asc = true):array  
    {
        uasort($array, function($a, $b) use ($property, $asc) 
        {
            return $asc ? $a[$property] - $b[$property] : $b[$property] - $a[$property];
        });

        return $array;
    }

    //----------------------------------------
    //MERGER
    //merges two blocks togehter (in $arrayPages[$page]['content'])
    static public function mergeBlocks(array &$obj, int $baseIndex, int $appendIndex, bool $resetIndex = true ):void  
    {
                
        $objBase =      &$obj['content'][$baseIndex];
        $objAppend =    &$obj['content'][$appendIndex];

        if($objBase['tag'] === "text" && $objAppend['tag'] === "text"  )
        {
            $txt1 = sys::strtoupper($objBase['content']);
            $txt2 = $objBase['content'];
            if($txt1 === $txt2)
            {
                $objAppend['content'] = sys::strtoupper($objAppend['content']);      
            }
        }


        $objBase['content'] .=  $objAppend['content']; 
        $objBase['left']     =  min([$objBase['left'],$objAppend['left']]);
        $objBase['top']      =  min([$objBase['top'],$objAppend['top']]);

        //calc new width
        $finalLeft1 =  $objBase['left'] +  $objBase['width'];
        $finalLeft2 =  $objAppend['left'] +  $objAppend['width'];
        $objBase['width'] = max([$finalLeft1,$finalLeft2]) - $objBase['left'];

        //calc new height
        $finalTop1 = $objBase['top'] + $objBase['height'];
        $finalTop2 = $objAppend['top'] + $objAppend['height'];
        $objBase['height'] = max([$finalTop1,$finalTop2]) - $objBase['top'];

        unset($obj['content'][$appendIndex]);

        if($resetIndex)
        {
            $obj['content'] = array_values($obj['content']); //re-index data
        }
        
    }

    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //HTML OUTPUT
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //execute logical components. Rules:
    // 1) All done by object reference func(&$obj) { ... }
    // 2) Always apply one single method process($obj)
	// 3) try to keep classes in order

    private static function setRulesLogic(int $page): void
    {
        
        $obj = &digi_pdf_to_html::$arrayPages[$page]; 
       
        self::sortByTopThenLeftAsc($obj);
		
        pdf_to_html_remove_last_hyphen::process($obj);
        pdf_to_html_remove_odd_content::process($obj);
        pdf_to_html_filter_image_dimensions::process($obj);
        pdf_to_html_text_leftoffset_merging::process($obj);
        pdf_to_html_text_centered_merging::process($obj);
        pdf_to_html_text_columns_merging::process($obj);
        pdf_to_html_footer_removal::process($obj);
        pdf_to_html_header_removal::process($obj);
        pdf_to_html_text_leftoffset_groupnumbers::process($obj);
        pdf_to_html_text_intersect_groupnumbers::process($obj);
        pdf_to_html_text_centered_groupnumbers::process($obj);
		pdf_to_html_text_orphan_merging::process($obj);
    }

    //#########################################

    public static function returnPageHtml(int $page): ?string
    {
        if (!isset(self::$arrayPages[$page]) || sys::posInt($page) === 0) { return null; }
        self::setRulesLogic($page);
        return self::returnFinalHtml($page);
    }

    //#########################################
    //the final output of a html-page

    public static function returnFinalHtml(int $page): string
    {
       
        //----------------------------------------------------
        //apply default sorting first
        $obj = &digi_pdf_to_html::$arrayPages[$page]; //object for each page (note by reference!)
        self::sortByTopThenLeftAsc($obj);
    
        //----------------------------------------------------
        //gather groupNumbers together
        $objFinal = [];

        $arrayHandledGroup=[];
        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['groupNumber'] == 0) { $objFinal[] = $properties; }
            elseif(!in_array($properties['groupNumber'], $arrayHandledGroup))
            {
                $arrayHandledGroup[] = $properties['groupNumber'];
                foreach ($obj['content'] as $index2 => $properties2) 
                {
                        if($properties['groupNumber'] <> $properties2['groupNumber'] ) {continue;} 
                        $objFinal[] = $properties2;    
                }
            }
        }

        //----------------------------------------------------
        //Build DOM
        $dom =  new html_parser();
        $dom->setFullHtml("<html><body></body></html>");
        $body = $dom->tagName("body")[0];


        //output html
        $arrayHandledGroup=[];
        foreach ($objFinal as $item) 
        {
            $divGroup =     $dom->createElem("div");
            $divBlock =     $dom->createElem("div");
            
            if($item['groupNumber'] > 0)
            {
                    $idName = "div_group_".$item['groupNumber'];
                    if(!in_array($item['groupNumber'],$arrayHandledGroup) )
                    {
                        $arrayHandledGroup[]=$item['groupNumber'];
                        $dom->setAttribute($divGroup,"id",$idName);
                        $dom->setCssProperty($divGroup,"border","2px solid orange"); 
                        $dom->setCssProperty($divGroup,"margin-top","15px"); 
                        $dom->setCssProperty($divGroup,"margin-bottom","15px"); 
                        $dom->appendLast($body,$divGroup);
                    }
                    else
                    {
                        $divGroup = $dom->id($idName);
                    }

                    $dom->appendLast($divGroup,$divBlock);
            }
            else
            {
                $dom->appendLast($body,$divBlock);      
            }

            
            if ($item['tag'] === 'text') 
            {
              
                if(isset($item['fontId']) && isset(self::$arrayFonts[$item['fontId']]))
                {
                    $arr = self::$arrayFonts[$item['fontId']];
                    $dom->setCssProperty($divBlock,"color",$arr['color']);
                    $dom->setCssProperty($divBlock,"font-size",$arr['size']."px");
                }

                $dom->setCssProperty($divBlock,"padding","10px");
                $dom->setCssProperty($divBlock,"border","1px dashed #777777");
                $dom->setCssProperty($divBlock,"margin","10px");
                $dom->setInnerHTML($divBlock,$item['content']);	
            } 
            else 
            {
                
                
                $imgPath = self::$processFolder . '/' . $item['content'];
                $blob = files::fileGetContents($imgPath);
                $src = images::base64FromBlob($blob, strtolower(pathinfo($imgPath, PATHINFO_EXTENSION)));

                $img =     $dom->createElem("img");
                $dom->setAttribute($img,"src",$src);
                $dom->setAttribute($img,"data-basename",basename($imgPath));
                $dom->appendLast($divBlock,$img);
                $dom->setCssProperty($divBlock,"text-align","center");

            } 
        }

        return $dom->innerHTML($body);

    }


    //##################################
   



}
