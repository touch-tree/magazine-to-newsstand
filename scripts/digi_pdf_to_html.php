<?php
declare(strict_types=1);
//######################################

/*
    using Poppler functions
    $pdfPath =  "D:/tempPdfs/italie magazine 2021_2_iPAD.pdf";
    digi_pdf_to_html::process($pdfPath,9,10);

    //collect html output per page:

    //-------

    foreach (digi_pdf_to_html::$arrayPages as $page => $value) 
    {
        $html  = digi_pdf_to_html::returnPageHtml($page);
    }


*/

class digi_pdf_to_html
{

    static public $arrayPages = [];
    static public $processFolder = null;

    static private $isInitiated = false;
    static private $applicationFolder = null;
    static private $baseCommand = null;
    static private $filePrefix = "content";

    //###########################################
    static public function process($pdfPath, $pageNumberStart = null, $pageNumberFinal = null)
    {
        if (!is_file($pdfPath)) {
            sys::error("pdf-path '" . $pdfPath . "' is invalid ");
        }
        self::$baseCommand = dirname(__DIR__) . "/bin/pdftohtml";

        //---------
        //setup temporary folder
        self::$processFolder = files::standardizePath(settings::server()['tempFolder'] . "/" . sys::databaseDir() . "/" . md5($pdfPath) . "/");
        if (is_dir(self::$processFolder)) {
            files::removeFolder(self::$processFolder);
        }
        files::createDir(self::$processFolder);

        //---------
        //the html content (note must be xml, as this version contains image x-y-location data)
        $params = array(
            "xml" => [null, null],
            "fontfullname" => [null, null],
            "p" => [null, null],
            "c" => [null, null]
        );


        if (isset($pageNumberStart)) {
            $params['f'] = [$pageNumberStart, " "];
        }
        if (isset($pageNumberFinal)) {
            $params['l'] = [$pageNumberFinal, " "];
        }

        $command = self::$baseCommand . shell::extractParams($params) . ' ' . escapeshellarg($pdfPath) . ' ' . self::$processFolder . "/" . self::$filePrefix;
        $out = shell::command($command, self::$processFolder);

        //-------
        //collect files-data
        self::collectContent();

        //------
        if (!self::$isInitiated) {
            self::$isInitiated = true;
            $dir = __DIR__ . "/pdf_to_html";
            set_include_path(implode(PATH_SEPARATOR,
                array(get_include_path(), $dir)));
            spl_autoload_register();
        }


    }

    //###########################################
    static private function collectContent()
    {
        $contentFile = self::$filePrefix . ".xml";
        $contentPath = files::standardizePath(self::$processFolder . "/" . $contentFile);
        if (!is_file($contentPath)) {
            sys::error("content-path '" . $contentPath . "' is invalid ");
        }
        $dom = new html_parser();
        $xml = files::fileGetContents($contentPath);
        $dom->setFullHtml($xml);
        $pages = $dom->tagName('page');
        $loop = $pages->length;

        self:: $arrayPages = [];


        for ($n = 0; $n < $loop; $n++) {

            $pageNumber = $dom->getAttribute($pages[$n], "number");
            self::$arrayPages[$pageNumber] = [];
            self::$arrayPages[$pageNumber]['pageWidth'] = $dom->getAttribute($pages[$n], "width");
            self::$arrayPages[$pageNumber]['pageHeight'] = $dom->getAttribute($pages[$n], "height");
            self::$arrayPages[$pageNumber]['tag'] = [];
            self::$arrayPages[$pageNumber]['top'] = [];
            self::$arrayPages[$pageNumber]['left'] = [];
            self::$arrayPages[$pageNumber]['height'] = [];
            self::$arrayPages[$pageNumber]['width'] = [];
            self::$arrayPages[$pageNumber]['content'] = [];
            self::$arrayPages[$pageNumber]['fontId'] = [];
            self::$arrayPages[$pageNumber]['groupNumber'] = [];
            self::$arrayPages[$pageNumber]['orderNumber'] = [];
            self::$arrayPages[$pageNumber]['isDeletable'] = [];

            $nodes = $dom->tagName('*', $pages[$n]);
            $loop2 = $nodes->length;
            for ($i = 0; $i < $loop2; $i++) {

                $tag = $dom->returnNodeName($nodes[$i]);
                if (!in_array($tag, ["text", "image"])) {
                    continue;
                }
                if (!$dom->hasAttribute($nodes[$i], "top")) {
                    continue;
                }
                if (!$dom->hasAttribute($nodes[$i], "left")) {
                    continue;
                }
                if (!$dom->hasAttribute($nodes[$i], "height")) {
                    continue;
                }
                if (!$dom->hasAttribute($nodes[$i], "width")) {
                    continue;
                }
                if ($tag === "text" && $nodes[$i]->textContent === '') {
                    continue;
                }
                $top = $dom->getAttribute($nodes[$i], "top");
                $left = $dom->getAttribute($nodes[$i], "left");
                $height = $dom->getAttribute($nodes[$i], "height");
                $width = $dom->getAttribute($nodes[$i], "width");

                if ($top < 0 or $left < 0 or $height <= 0 or $width <= 0) {
                    continue;
                }

                $content = null;
                $fontId = null;

                if ($tag === "image") {
                    $src = $dom->getAttribute($nodes[$i], "src");
                    if (is_file($src)) {
                        $content = basename($src);
                    }
                }

                if ($tag === "text") {
                    $content = $dom->innerHTML($nodes[$i]);
                    if ($dom->hasAttribute($nodes[$i], "font")) {
                        $fontId = $dom->getAttribute($nodes[$i], "font");
                    }
                }

                self::$arrayPages[$pageNumber]['tag'][] = $tag;
                self::$arrayPages[$pageNumber]['top'][] = $top;
                self::$arrayPages[$pageNumber]['left'][] = $left;
                self::$arrayPages[$pageNumber]['height'][] = $height;
                self::$arrayPages[$pageNumber]['width'][] = $width;
                self::$arrayPages[$pageNumber]['content'][] = $content;
                self::$arrayPages[$pageNumber]['fontId'][] = $fontId;
                self::$arrayPages[$pageNumber]['groupNumber'][] = 0;
                self::$arrayPages[$pageNumber]['orderNumber'][] = 0;
                self::$arrayPages[$pageNumber]['isDeletable'][] = false;
            }
        }

        //--------------
        //sort by page number (asc)
        ksort(self::$arrayPages);

        //-------------
        //add font information to the array
        self::$arrayPages = ['fonts' => []] + self::$arrayPages;
        $fonts = $dom->tagName('fontspec');
        $loop = $fonts->length;

        for ($n = 0; $n < $loop; $n++) {
            $fontId = $dom->getAttribute($fonts[$n], "id");
            self::$arrayPages['fonts'][$fontId] = [];
            self::$arrayPages['fonts'][$fontId]['size'] = $dom->getAttribute($fonts[$n], "size");
            self::$arrayPages['fonts'][$fontId]['family'] = $dom->getAttribute($fonts[$n], "family");
            self::$arrayPages['fonts'][$fontId]['color'] = $dom->getAttribute($fonts[$n], "color");
        }
    }

    //###########################################
    //###########################################
    //###########################################
    //HTML BUILDER!!!!!
    //###########################################
    //###########################################
    //###########################################

    public static function returnPageHtml($page): ?string
    {
        if (sys::posInt($page) === 0) {
            return null;
        }

        if (!isset(self::$arrayPages[$page])) {
            return null;
        }

        self::setRulesLogic($page);

        return self::returnFinalHtml($page);
    }

    //###########################################

    static private function returnFinalHtml($page): string
    {
        $arrayHtmlBlocks = [];

        array_multisort
        (
            self::$arrayPages[$page]['groupNumber'], SORT_ASC,
            self::$arrayPages[$page]['orderNumber'], SORT_ASC,
            self::$arrayPages[$page]['tag'],
            self::$arrayPages[$page]['height'],
            self::$arrayPages[$page]['width'],
            self::$arrayPages[$page]['content'],
            self::$arrayPages[$page]['fontId'],
            self::$arrayPages[$page]['top'],
            self::$arrayPages[$page]['left'],
            self::$arrayPages[$page]['isDeletable']
        );


        $obj = self::$arrayPages[$page];
        $len = count($obj['top']);
        $currentGroup = 0;
        $content = "";

        for ($n = 0; $n < $len; $n++) {
            if ($obj['isDeletable'][$n]) {
                continue;
            }

            //group management
            if ($currentGroup <> $obj['groupNumber'][$n] && $currentGroup > 0) {
                if (sys::length($content) > 0) {
                    $arrayHtmlBlocks[] = $content;
                }

                $content = "";
            }

            $currentGroup = $obj['groupNumber'][$n];

            //tag management
            if ($obj['tag'][$n] === "text") {
                $content .= $obj['content'][$n];
            } else {
                $img = self::$processFolder . "/" . $obj['content'][$n];

                $blob = files::fileGetContents($img);
                $src = images::base64FromBlob($blob, strtolower(pathinfo($img, PATHINFO_EXTENSION)));

                $content .= "<div><img id=\"" . basename($img) . "\" src='" . $src . "'  /></div>";
            }
        }

        if (sys::length($content) > 0) {
            $arrayHtmlBlocks[] = $content;
        }

        return implode("<hr>", $arrayHtmlBlocks);

    }

    //########################################################

    static private function setRulesLogic($page)
    {
        pdf_to_html_default::process($page);
        pdf_to_html_filter_image_dimensions::process($page);
        pdf_to_html_group_to_paragraphs::process($page);

        //pdf_to_html_logic1:process($page); 
        //pdf_to_html_logic2:process($page); 
    }

    //###########################################


}

?>