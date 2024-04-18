<?php
declare(strict_types=1);

class digi_pdf_to_html
{
    static public array $arrayPages =   [];
    static public array $arrayFonts =   [];
    static public ?string $processFolder = null;
    static private bool $isInitiated = false;
    static private ?string $baseCommand = null;
    static private string $filePrefix = 'content';

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

    /**
     * Process a PDF.
     *
     * @param string $pdfPath
     * @param int|null $pageNumberStart
     * @param int|null $pageNumberFinal
     * @return void
     */
    
    public static function process(string $pdfPath, ?int $pageNumberStart = null, ?int $pageNumberFinal = null): void
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

    //##################################################################################

    
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
            $pageWidth  =  $dom->getAttribute($page, 'width');
            $pageHeight  =  $dom->getAttribute($page, 'height');

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

                if ($top > $pageHeight || $left > $pageWidth ) {
                    continue;
                }

                $content =  null;
                $fontId =   null;

                if ($tag === 'image') {
                    $src = $dom->getAttribute($node, 'src');
                    if (is_file($src)) {
                        $content = basename($src);
                    }
                } else {
                    if( sys::length($node->textContent) == 0) {continue;}
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
                    'groupNumber' => 0
                ];
            }
        }

        // Sort by page number (asc)
        ksort(self::$arrayPages);

        // Add font information to self::$arrayFonts
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

    // Get the new group number from a page.
     
    public static function getNewGroupNumber(int $page): int
    {
        $obj = self::$arrayPages[$page]['content'];
        $groupNumbers = array_column($obj, 'groupNumber');
        $max= max($groupNumbers);
        return $max + 1;
    }

    //#################################################################################
    // return a set of index values from self::$arrayPages[$page]. Note that the index-numbers themselves are preserved.

    static public function filterSelectedIndexes($obj, array $arrayIndexes):array 
    {
        $array = $obj['content'];    
        
        $values = [];
            
        foreach($arrayIndexes as $index) 
        {
                if(isset($array[$index])) 
                {
                    $values[$index] = $array[$index];
                }
        }
        
        return $values;
    }

    //#################################################################################
    //sorts the base-array by propery (asc or desc) . Note that the index-numbers themselves are preserved.
    static public function sortArrayByProperty(array $array, string $property, bool $asc = true):array  
    {
        uasort($array, function($a, $b) use ($property, $asc) {
            return $asc ? $a[$property] - $b[$property] : $b[$property] - $a[$property];
        });
        return $array;
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

    public static function returnPageHtml(int $page): ?string
    {
        if (!isset(self::$arrayPages[$page]) || sys::posInt($page) === 0) {
            return null;
        }

        self::setRulesLogic($page);

        return self::returnFinalHtml($page);
    }

    //#########################################

    public static function returnFinalHtml(int $page): string
    {
        $blocks = [];
        $content = '';

        //print_r(self::$arrayPages[$page]['content']);exit;

        //sort final array on 'top', then groupnumber
        usort(self::$arrayPages[$page]['content'], function($a, $b) 
        {
            // Compare the 'top' property
            $topComparison = $a['top'] <=> $b['top'];
            
            // If 'top' is the same, compare the 'groupNumber' property
            if ($topComparison === 0) 
            {
                return $a['groupNumber'] <=> $b['groupNumber'];
            }
            
            return $topComparison;
        });

        //----------------------------------------------------
        

        //output html
        $currentGroupId=    0;
        $html =             "";
        foreach (self::$arrayPages[$page]['content'] as $item) 
        {
            if ($item['tag'] === 'text') 
            {
                $html .= "<hr>". $item['content'];
            } 
            else 
            {

                $img = self::$processFolder . '/' . $item['content'];

                // Prepare image element

                $blob = files::fileGetContents($img);
                $src = images::base64FromBlob($blob, strtolower(pathinfo($img, PATHINFO_EXTENSION)));

                $html .= '<div><img id="' . basename($img) . '" src="' . $src . '" alt=""/></div>';
            }
        }

        return $html;
    }

    //##################################

    private static function setRulesLogic(int $page): void
    {
        $obj = &digi_pdf_to_html::$arrayPages[$page]; //object for each page (note by reference!)
        pdf_to_html_default::process($obj);
        pdf_to_html_remove_odd_content::process($obj);
        pdf_to_html_filter_image_dimensions::process($obj);
        pdf_to_html_text_same_left_offset::process($obj);
    }

    //##################################
}
