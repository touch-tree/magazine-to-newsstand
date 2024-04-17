<?php
declare(strict_types=1);

class digi_pdf_to_html
{
    static public array $arrayPages = [];
    static public ?string $processFolder = null;
    static private bool $isInitiated = false;
    static private ?string $baseCommand = null;
    static private string $filePrefix = 'content';

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

                $content = null;
                $fontId = null;

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
                    'groupNumber' => 0,
                    'isDeletable' => false
                ];
            }
        }

        // Sort by page number (asc)

        ksort(self::$arrayPages);

        // Add font information to the array
        self::$arrayPages =  ['fonts' => []] + self::$arrayPages; 
        foreach ($dom->tagName('fontspec') as $font) {
            self::$arrayPages['fonts'][$dom->getAttribute($font, 'id')] = [
                'size' => $dom->getAttribute($font, 'size'),
                'family' => $dom->getAttribute($font, 'family'),
                'color' => $dom->getAttribute($font, 'color')
            ];
        }

        print_r(self::$arrayPages);exit;
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

        self::setRulesLogic($page);

        return self::returnFinalHtml($page);
    }

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

        // Add more processors here.
    }
}
