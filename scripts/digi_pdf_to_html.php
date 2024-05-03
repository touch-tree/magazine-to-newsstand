<?php
declare(strict_types=1);

class digi_pdf_to_html
{
    static public array $arrayPages =       [];
    static public array $arrayFonts =       [];
    static public ?int  $articleId =        null;
    static public ?string $processFolder =  null;

    static private bool $isInitiated =      false;
    static private ?string $baseCommand =   null;
    static private string $filePrefix =     'content';
    static private ?int $pageNumber =       null;
    //##################################################################################
    //##################################################################################
    //##################################################################################
    //PROCESSING
    //##################################################################################
    //##################################################################################
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

    private static function init(): void
    {
            if(self::$isInitiated) { return ;}
            self::$isInitiated=true;
            $out = installation::popplerStatus();
            if(!$out['success'])                    { sys::error("digi_pdf_to_html-class can not be used due to missing instgalled software on the server "); }
            if(isset($out['baseCommand']))          { self::$baseCommand = $out['baseCommand']; }
    
            //add extra folders with their own classes domains
            set_include_path(implode(PATH_SEPARATOR, 
            array(get_include_path(),
            __DIR__."/pdf_to_html/precleanup"
            ,__DIR__."/pdf_to_html/textMerger"
            ,__DIR__."/pdf_to_html/grouping")));
            spl_autoload_register();   
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
                    'pageNumber' => sys::posInt($pageNumber),
                    'pageWidth' =>  sys::posInt($dom->getAttribute($page, 'width')),
                    'pageHeight' => sys::posInt($dom->getAttribute($page, 'height'))
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

                $top =     sys::posInt($dom->getAttribute($node, 'top'));
                $left =    sys::posInt($dom->getAttribute($node, 'left'));
                $height =  sys::posInt($dom->getAttribute($node, 'height'));
                $width =   sys::posInt($dom->getAttribute($node, 'width'));

                //---------------------
                //elements out of visual range
                if ($top < 0 || $left < 0 || $height <= 0 || $width <= 0)   { continue; }
                if ($top >= $pageHeight || $left >= $pageWidth )            { continue; }
                if ($left <=0  )                                            { continue; } /* likely from previous page */
                if ($top <= 0)                                              { continue; } /* likely from previous page */
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
                    'fontSize' => null,
                    'fontColor' => null,
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

        //---------------------------
        //assign font properties to main data obj
        foreach( self::$arrayPages as $page => &$nodes) 
        {
            foreach($nodes['content'] as $index => &$properties) 
            {
                if($properties['tag'] === "image") { continue; }
                $fontId = $properties['fontId'];
                $properties['fontColor'] = self::$arrayFonts[$fontId]['color'];
                $properties['fontSize']  = self::$arrayFonts[$fontId]['size'];     
            }
        }
 
    }


    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //GENERATE HTML
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    
    public static function returnHtml(int $page): ?string
    {
        if (!isset(self::$arrayPages[$page]) || sys::posInt($page) === 0) { return null; }
        self::$pageNumber = $page;
        self::parseContent($page);
        return self::buildHtml($page);
    }


    //###################################################################################

    private static function buildHtml(int $page): string
    {

        $obj = &self::$arrayPages[$page];
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
                    //$dom->setCssProperty($divBlock,"color",$arr['color']);
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
                $dom->setCssProperty($img,"max-width","100%");

            } 
        }

        return $dom->innerHTML($body);

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

    //SORTING. Sorts date (self::$arrayPages[$page]) by top-position (asc), and then left-position(asc).
    static public function sortByTopThenLeftAsc(&$obj):void                                                                     { if(!isset($obj['content'])) { sys::error("sortByTopThenLeftAsc requires object to have the content-property");}  usort($obj['content'], function ($item1, $item2)  {  if ($item1['top'] == $item2['top']) { return $item1['left'] <=> $item2['left']; }  return $item1['top'] <=> $item2['top'];  }); }

    //FILTER ON PROPERTY. Note that the index-numbers themselves are preserved.
    static public function returnProperties(&$obj, string $property, $value, ?bool $isGrouped=null):array                       { if(!isset($obj['content'])) { sys::error("returnProperties requires the object to have the content-property");}   $result = array();  $prop = $obj['content']; foreach($prop as $key => $item) {  if(isset($isGrouped)) {if($isGrouped && $item['groupNumber'] == 0 ) { continue; }   if(!$isGrouped && $item['groupNumber']> 0 ) { continue; }    } if(isset($item[$property]) && $item[$property] == $value)   {    $result[$key] = $item;   } } return $result;  }

    //BOUNDARY DATA. return boundary-data from one or more nodes (text or images)
    static public function returnBoundary(array &$obj, array $indexes):array                                                    {$block=[]; $block['left']= 0; $block['top']= 0; $block['width']= 0; $block['height']=  0; $block['maxLeft']= 0; $block['maxTop']= 0; $block['pagePercentageStartTop']=   0; $block['pagePercentageStartLeft']=  0; $block['pagePercentageEndTop']=     0; $block['pagePercentageEndLeft']=    0; $len = sizeof($indexes);for($n=0;$n<$len;$n++) { $index= $indexes[$n];$properties =       $obj['content'][$index]; if($block['left'] ==0 or $block['left'] > $obj['content'][$index]['left'] ) {  $block['left'] = $obj['content'][$index]['left']; }   if($block['top'] == 0 or $block['top'] > $obj['content'][$index]['top'] ){    $block['top'] = $obj['content'][$index]['top']; }  $maxLeft = $obj['content'][$index]['left'] + $obj['content'][$index]['width']; if($maxLeft > $block['maxLeft']) { $block['maxLeft'] =  $maxLeft;    } $maxTop = $obj['content'][$index]['top'] + $obj['content'][$index]['height']; if($maxTop > $block['maxTop']) {    $block['maxTop'] =  $maxTop;  }  $block['width'] =  $block['maxLeft'] - $block['left'];  $block['height'] = $block['maxTop'] -  $block['top']; } $block['pagePercentageStartTop'] =      round(($block['top'] / $obj['meta']['pageHeight']) * 100,2);  $block['pagePercentageStartLeft'] =     round(($block['left'] / $obj['meta']['pageWidth']) * 100,2); $block['pagePercentageEndTop'] =        round(( $block['maxTop'] / $obj['meta']['pageHeight']) * 100,2);  $block['pagePercentageEndLeft'] =       round(( $block['maxLeft'] / $obj['meta']['pageWidth']) * 100,2); return $block; }

    //RE-INDEX DATA
    static public function reIndex(array &$obj):void                                                                            { if(!isset($obj['content'])) { sys::error("reIndex requires the object to have the content-property");}  $obj['content'] = array_values ($obj['content']);  }

    //REMOVE INDEX. Note re-indexing also takes place
    static public function removeIndex(array &$obj, int $index):void                                                            {if(!isset($obj['content'])) { sys::error("removeIndex requires the object to have the content-property");} unset($obj['content'][$index]); self::reIndex($obj);}

    //WITHIN BOUNDARY
    static public function nodeWithinBoundary(array $properties, array $objBoundary):bool                                       { $maxLeft = $properties['left'] + $properties['width'] ; $maxTop  = $properties['top'] + $properties['height'] ; if ($properties['left'] >= $objBoundary['left'] && $maxLeft <= $objBoundary['maxLeft'] && $properties['top'] >= $objBoundary['top'] && $maxTop <= $objBoundary['maxTop']) {return true;} return false;}

   //OVERLAP BOUNDARY
   static public function nodeOverlapsBoundary(array $properties, array $objBoundary):bool                                      { $left =  $properties['left'];  $top  =  $properties['top'];  $maxLeft =  $properties['left'] + $properties['width'];  $maxTop =  $properties['top'] + $properties['height'];  if ($left >  $objBoundary['maxLeft'] ||  $maxLeft < $objBoundary['left']) { return false;} if ($top >  $objBoundary['maxTop']|| $maxTop <= $objBoundary['top']) {return false;} return true; }

    //MERGE NODES. Merges two blocks together (in $arrayPages[$page]['content']) and (by default) applies reIndex(). Note the base-Node will get new dimensions (top, left, height etc...) 
    static public function mergeNodes(array &$obj, int $baseIndex, int $appendIndex, bool $resetIndex = true ):void             { $objBase =  &$obj['content'][$baseIndex];  $objAppend =    &$obj['content'][$appendIndex];   if($objBase['tag'] === "text" && $objAppend['tag'] === "text"  ){ $txt1 = sys::strtoupper($objBase['content']); $txt2 = $objBase['content']; if($txt1 === $txt2 && sys::length($txt1) > 1) {  $objAppend['content'] = sys::strtoupper($objAppend['content']);   } } $objBase['content'] .=  $objAppend['content'];  $objBase['left']     =  min([$objBase['left'],$objAppend['left']]); $objBase['top']      =  min([$objBase['top'],$objAppend['top']]);  /* calc new width */ $finalLeft1 =  $objBase['left'] +  $objBase['width']; $finalLeft2 =  $objAppend['left'] +  $objAppend['width']; $objBase['width'] = max([$finalLeft1,$finalLeft2]) - $objBase['left']; /* calc new height */  $finalTop1 = $objBase['top'] + $objBase['height'];  $finalTop2 = $objAppend['top'] + $objAppend['height']; $objBase['height'] = max([$finalTop1,$finalTop2]) - $objBase['top'];  unset($obj['content'][$appendIndex]);  if($resetIndex) {self::reIndex($obj);} }

    //COLLECT VALUE->INDEXES as array. Note Value is used as key, but should not be used for calculations because it combines other values based on the margin, and will take the most used value as key.
    static public function collectPropertyValues(array $nodes, string $property, int $margin):array                             { $arrayCollection = [];/* collect items first without any range */ foreach( $nodes as $index => $properties)  {  $value = $properties[$property]; if(!sys::isInt($value)) { continue; }  if(!isset($arrayCollection[$value])){ $arrayCollection[$value]=[]; }  $arrayCollection[$value][]=$index;} /*  apply margin grouping of similar key values  */ ksort($arrayCollection); $result = array();$temp =  array(); foreach ($arrayCollection as $key => $value)  { if (empty($temp))  {    $temp[$key] = $value; }  else  {  end($temp);   $last_key = key($temp);   if ($key - $last_key <= $margin) { $temp[$key] = $value;  } else { $result[] = $temp; $temp = array($key => $value);  }  } } if (!empty($temp)) { $result[] = $temp;}   $arrayCollection = $result; /*  apply merger of grouping grouping */ $arr=[];  foreach ($arrayCollection as $key => $collection)   { $arrayKeys = array_keys($collection); if(sizeof($collection)==1) {    $arr[$arrayKeys[0]] = $collection[$arrayKeys[0]];  } else{ $maxCount = 0;   $maxKey =   0;   $subArr =   [];  foreach ($collection as $key => $subArray)   {  if (count($subArray) > $maxCount)  {   $maxCount = count($subArray); $maxKey = $key;  }  $subArr = array_merge( $subArr , $subArray ); }  $arr[$maxKey] =  $subArr;  } } $arrayCollection = $arr; /* sort by top ASC, left ASC */ $obj = &self::$arrayPages[self::$pageNumber]; foreach ($arrayCollection as $value => $indexes)  { $len = sizeof($indexes); if($len<=1){continue;} $arrTop =   []; $arrLeft =  []; for($n=0;$n<$len;$n++) { $indx = $indexes[$n]; $arrTop[] =  $obj['content'][$indx]['top'];   $arrLeft[] = $obj['content'][$indx]['left']; } array_multisort($arrTop, SORT_ASC, $arrLeft, SORT_ASC, $indexes); $arrayCollection[$value] = $indexes;} ksort($arrayCollection);return $arrayCollection;  }
    
    //COLLECT Nodes From indexes. Note that the index-numbers themselves are preserved.
    static public function returnNodesFromIndexes(array &$obj, array $indexes ):array                                           {if(!isset($obj['content'])) { sys::error("returnNodesFromIndexes requires the object to have the content-property");}$out = [];$prop = $obj['content']; foreach($prop as $index => $properties) {  if(!in_array($index,$indexes)){continue;}  $out[$index]=$properties;} return $out;  }

    //COLLECT MIN/MAX PROPERY VALUE (of $indexes). Use only for numeric values
    static public function returnMinMaxProperyValue(array &$obj, string $property, array $indexes, bool $isMax=true ):int       {$nodes = self::returnNodesFromIndexes($obj,$indexes);$out = 0;foreach($nodes as $index => $properties) {  $value = $properties[$property]; if($out == 0) { $out = $value; } if($isMax and $value > $out)    { $out = $value;} if(!$isMax and $value < $out)   { $out = $value;}} return $out;}

    //RETURN NEW GROUPNUMBER
    public static function getNewGroupNumber(&$obj): int                                                                        {$groupNumbers = array_column($obj['content'], 'groupNumber');return max($groupNumbers) + 1;}

    //CREATE GROUPS from nodes
    static public function groupNodes(array &$obj, array $indexes):bool                                                          {$nodes =   self::returnNodesFromIndexes($obj,$indexes);$groups =    self::collectPropertyValues($nodes,"groupNumber",0);$arrayGroupNumbers =    array_values(array_unique(array_keys($groups)));if(!in_array(0,$arrayGroupNumbers)) { return false; } $groupId = max($arrayGroupNumbers); if($groupId == 0) {$groupId = self::getNewGroupNumber($obj);} foreach ($nodes as $index => $properties)  {if($properties['groupNumber'] > 0 ) {continue;} $obj['content'][$index]['groupNumber'] = $groupId; } return true;}
    
    //GET ALL ASSIGNED GROUPS
    static public function returnAssignedGroups(array &$obj):array                                                              {$groupNumbers = array_map(function($item) { return $item['groupNumber'];}, $obj['content']); $groupNumbers = array_filter($groupNumbers, function($number) { return $number > 0;}); $groupNumbers = array_values(array_unique($groupNumbers)); return $groupNumbers;}
    
    //BOUNDARY GROUP DATA. return boundary-data from a given groupnumber
    static public function returnGroupBoundary(array &$obj, int $groupNumber):array                                             { $block=[];  $block['left']= 0;  $block['top']= 0;  $block['width']= 0;  $block['height']=  0;  $block['maxLeft']= 0;  $block['maxTop']= 0;  $block['pagePercentageStartTop']=   0;  $block['pagePercentageStartLeft']=  0;  $block['pagePercentageEndTop']=     0;  $block['pagePercentageEndLeft']=    0; $nodes = self::returnProperties( $obj, "groupNumber", $groupNumber,true );foreach ($nodes as $index => $properties) { $maxLeft =  $properties['left'] +  $properties['width'];  $maxTop =   $properties['top'] +  $properties['height']; if( $block['left'] == 0) { $block['left'] = $properties['left'];} if( $block['top'] == 0) { $block['top'] = $properties['top'];} if( $block['maxLeft'] == 0) { $block['maxLeft'] = $maxLeft;} if( $block['maxTop'] == 0) { $block['maxTop'] =  $maxTop;}  if($properties['left'] < $block['left'] )   {  $block['left'] = $properties['left'];} if($properties['top'] < $block['top'] )     {  $block['top'] = $properties['top'];}  if($maxLeft> $block['maxLeft'] )     {  $block['maxLeft'] = $maxLeft;}  if($maxTop > $block['maxTop'] )     {  $block['maxTop'] = $maxTop;} } $block['width'] =   $block['maxLeft'] - $block['left']; $block['height'] =  $block['maxTop'] - $block['top']; $block['pagePercentageStartTop'] =  round(($block['top'] / $obj['meta']['pageHeight']) * 100,2);   $block['pagePercentageStartLeft'] =     round(($block['left'] / $obj['meta']['pageWidth']) * 100,2);   $block['pagePercentageEndTop'] =        round(( $block['maxTop'] / $obj['meta']['pageHeight']) * 100,2);    $block['pagePercentageEndLeft'] =       round(( $block['maxLeft'] / $obj['meta']['pageWidth']) * 100,2);   return $block; }
    
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //CONTENT PARSING
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
   
    private static function parseContent(int $page): void
    {
        $obj = &self::$arrayPages[$page];     
        
        //----------------------------------------
        //pre-cleaning up. Anything before any merger attempt is performed
        new pth_removeInvisibleTexts($obj);
        new pth_removeStrangeTexts($obj);
        new pth_removeLastHyphen($obj);
        new pth_removeStrangeSizedImages($obj);
        new pth_removeBlurredImages($obj);
        new pth_removeNearWhiteImages($obj);
        new pth_removeHeader($obj);
        new pth_removeFooter($obj);
        new pth_removeOverlappingImages($obj);

        
        //---------------------------------------
        //text merger (preserve sequence!!!!!!!) before any grouping attempt is performed
        new pth_floatingTexts($obj);
        new pth_leftAlignedTexts($obj);
        new pth_rightAlignedTexts($obj); //must come after left alignment
        new pth_centeredTexts($obj);
        new pth_capitalStartLetter($obj);
        new pth_textColumns($obj);
        

        //--------------------------------------
        //grouping
        new pth_leftAlignedNodes($obj);
        new pth_centeredNodes($obj);
        new pth_ungroupedTextWithinOtherUngroupedText($obj);
        new pth_ungroupedTextWithinGroupedBoundary($obj);
        new pth_ungroupedImageWithinGroupedBoundary($obj);
        new pth_ungroupedImageOverlapGroupedBoundary($obj);
        new pth_ungroupedTextOverlapGroupedBoundary($obj);
        

        
        //code Josh here....
        

    }

    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //END
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################

}
