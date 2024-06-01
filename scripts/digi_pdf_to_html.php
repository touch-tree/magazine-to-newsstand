<?php
declare(strict_types=1);

class digi_pdf_to_html
{
    static public array $arrayPages =       [];
    static public array $arrayFonts =       [];
    static public ?int  $articleId =        null;
    static public ?string $processFolder =  null;
    static public ?int $pageNumber =        null;

    static private bool $isInitiated =      false;
    static private ?string $baseCommand =   null;
    static private string $filePrefix =     'content';



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
            ,__DIR__."/pdf_to_html/grouping"
            ,__DIR__."/pdf_to_html/postgrouping"
            ,__DIR__."/pdf_to_html/postcleanup"
            )));
            spl_autoload_register();   
    }

    //##################################################################################

    private static function collectContent(): void
    {
        

        $path = files::standardizePath(self::$processFolder . '/' . self::$filePrefix . '.xml');
        if (!is_file($path)) {  sys::error('content - path does not exist: ' . $path); }
       
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
                'nodes' => []
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

                if($tag === "text")
                {
                    if ($left <=0  )                                         { continue; } /* likely from previous page */
                    if ($top <= 0  )                                         { continue; } /* likely from previous page */
                }

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

                self::$arrayPages[$pageNumber]['nodes'][] = [
                    'tag' => $tag,
                    'top' => $top,
                    'left' => $left,
                    'height' => $height,
                    'width' => $width,
                    'content' => $content,
                    'fontId' => $fontId,
                    'fontSize' => null,
                    'fontColor' => null,
                    'groupNumber' => 0,
                    'groupSequenceNumber' => 0
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
                'color' => strtolower($dom->getAttribute($font, 'color'))
            ];
        }

        //---------------------------
        //assign font properties to main data obj
        foreach( self::$arrayPages as $page => &$nodes) 
        {
            foreach($nodes['nodes'] as $index => &$properties) 
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
        self::parseContent();
        return self::buildHtml();
    }


    //###################################################################################

    private static function buildHtml(): string
    {
        $obj = &self::$arrayPages[self::$pageNumber]; 
        self::sortByTopThenLeftAsc();
    
        //----------------------------------------------------
        //gather groupNumbers together
       
        $objFinal = [];

        $arrayHandledGroup=[];
        foreach ($obj['nodes'] as $index => $properties) 
        {
            if($properties['groupNumber'] == 0) { $objFinal[$index] = $properties; }
            elseif(!in_array($properties['groupNumber'], $arrayHandledGroup))
            {
                $arrayHandledGroup[] = $properties['groupNumber'];

                //sort groupnumber by groupSequenceNumber asc (preserve original key-value)
                $groupNodes = self::returnProperties("groupNumber",$properties['groupNumber']);
                $groupNodes = self::sortNodesByProperty($groupNodes,"groupSequenceNumber");

                foreach ($groupNodes as $index2 => $properties2) 
                {
                        $objFinal[$index2] = $properties2;    
                }
            }
        }
        
        //----------------------------------------------------
        //Build DOM
        $dom =  new html_parser();
        $dom->setFullHtml("<html><head></head><body></body></html>");
        $body = $dom->tagName("body")[0];

        $divMain =     $dom->createElem("div");
        $dom->setAttribute($divMain,"data-pagenumber",self::$pageNumber);
        $dom->setAttribute($divMain,"data-pagewidth",self::$arrayPages[self::$pageNumber]['meta']['pageWidth']);
        $dom->setAttribute($divMain,"data-pageheight",self::$arrayPages[self::$pageNumber]['meta']['pageHeight']);

        $dom->appendLast($body,$divMain);

        $style =     $dom->createElem("style");
        $dom->appendLast($divMain,$style);
        $style->textContent="
        [data-groupnumber]      {border:2px solid orange;margin-top:15px;margin-bottom:15px;}
        .divText                {padding:10px; border:1px dashed #777777; margin: 10px;}
        .divImage               {text-align:center;}
        .divImage img           {max-width:100%;
        ";

        
        //output html
        $arrayHandledGroup=[];
        foreach ($objFinal as $index => $item) 
        {
 
            $divGroup =     $dom->createElem("div");
            $divBlock =     $dom->createElem("div");
            
            if($item['groupNumber'] > 0)
            {
                    $idName = "divGroup".$item['groupNumber'];
                    if(!in_array($item['groupNumber'],$arrayHandledGroup) )
                    {
                        $arrayHandledGroup[]=$item['groupNumber'];
                        $dom->setAttribute($divGroup,"id",$idName);
                        $dom->setAttribute($divGroup,"data-groupnumber",$item['groupNumber']); 
                        $dom->appendLast($divMain,$divGroup);

                        $boundary = self::returnGroupBoundary($item['groupNumber']);
                        $dom->setAttribute($divGroup,"data-left",$boundary['left']); 
                        $dom->setAttribute($divGroup,"data-top",$boundary['top']); 
                        $dom->setAttribute($divGroup,"data-width",$boundary['width']); 
                        $dom->setAttribute($divGroup,"data-height",$boundary['height']); 
                        $dom->setAttribute($divGroup,"data-right",$boundary['maxLeft']); 
                        $dom->setAttribute($divGroup,"data-bottom",$boundary['maxTop']); 
                        
                    }
                    else
                    {
                        $divGroup = $dom->id($idName);
                    }

                    $dom->appendLast($divGroup,$divBlock);
                    $dom->setAttribute($divBlock,"data-groupsequence",$item['groupSequenceNumber']); 
            }
            else
            {
                $dom->appendLast($divMain,$divBlock);      
            }

        
            $boundary = self::returnBoundary([$index]);
            $dom->setAttribute($divBlock,"data-left",$boundary['left']); 
            $dom->setAttribute($divBlock,"data-top",$boundary['top']); 
            $dom->setAttribute($divBlock,"data-width",$boundary['width']); 
            $dom->setAttribute($divBlock,"data-height",$boundary['height']); 
            $dom->setAttribute($divBlock,"data-right",$boundary['maxLeft']); 
            $dom->setAttribute($divBlock,"data-bottom",$boundary['maxTop']); 
            
        
            if ($item['tag'] === 'text') 
            {
              
                if(isset($item['fontId']) && isset(self::$arrayFonts[$item['fontId']]))
                {
                    $arr = self::$arrayFonts[$item['fontId']];
                    //$dom->setCssProperty($divBlock,"color",$arr['color']);
                    $dom->setCssProperty($divBlock,"font-size",$arr['size']."px");
                }

                $dom->setAttribute($divBlock,"class","divText"); 
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
                $dom->setAttribute($divBlock,"class","divImage"); 

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
    static public function sortByTopThenLeftAsc():void                                                                          { $obj = &self::$arrayPages[self::$pageNumber]; usort($obj['nodes'], function ($item1, $item2)  {  if ($item1['top'] == $item2['top']) { return $item1['left'] <=> $item2['left']; }  return $item1['top'] <=> $item2['top'];  }); }

    //FILTER ON PROPERTY. Note that the index-numbers themselves are preserved.
    static public function returnProperties(string $property, $value, ?bool $isGrouped=null):array                             { $obj = &self::$arrayPages[self::$pageNumber]; $result = array();  $prop = $obj['nodes']; foreach($prop as $key => $item) {  if(isset($isGrouped)) {if($isGrouped && $item['groupNumber'] == 0 ) { continue; }   if(!$isGrouped && $item['groupNumber']> 0 ) { continue; }    } if(isset($item[$property]) && $item[$property] == $value)   {    $result[$key] = $item;   } } return $result;  }

    //BOUNDARY DATA. return boundary-data from one or more nodes (text or images)
    static public function returnBoundary(array $indexes):array                                                                 {$obj = &self::$arrayPages[self::$pageNumber]; $block=[]; $block['left']= 0; $block['top']= 0; $block['width']= 0; $block['height']=  0; $block['maxLeft']= 0; $block['maxTop']= 0; $block['pagePercentageStartTop']=   0; $block['pagePercentageStartLeft']=  0; $block['pagePercentageEndTop']=     0; $block['pagePercentageEndLeft']=    0; $len = sizeof($indexes);for($n=0;$n<$len;$n++) { $index= $indexes[$n];$properties =       $obj['nodes'][$index]; if($block['left'] ==0 or $block['left'] > $obj['nodes'][$index]['left'] ) {  $block['left'] = $obj['nodes'][$index]['left']; }   if($block['top'] == 0 or $block['top'] > $obj['nodes'][$index]['top'] ){    $block['top'] = $obj['nodes'][$index]['top']; }  $maxLeft = $obj['nodes'][$index]['left'] + $obj['nodes'][$index]['width']; if($maxLeft > $block['maxLeft']) { $block['maxLeft'] =  $maxLeft;    } $maxTop = $obj['nodes'][$index]['top'] + $obj['nodes'][$index]['height']; if($maxTop > $block['maxTop']) {    $block['maxTop'] =  $maxTop;  }  $block['width'] =  $block['maxLeft'] - $block['left'];  $block['height'] = $block['maxTop'] -  $block['top']; } $block['pagePercentageStartTop'] =      round(($block['top'] / $obj['meta']['pageHeight']) * 100,2);  $block['pagePercentageStartLeft'] =     round(($block['left'] / $obj['meta']['pageWidth']) * 100,2); $block['pagePercentageEndTop'] =        round(( $block['maxTop'] / $obj['meta']['pageHeight']) * 100,2);  $block['pagePercentageEndLeft'] =       round(( $block['maxLeft'] / $obj['meta']['pageWidth']) * 100,2); return $block; }

    //RE-INDEX DATA
    static public function reIndex():void                                                                                       { $obj = &self::$arrayPages[self::$pageNumber]; $obj['nodes'] = array_values ($obj['nodes']);  }

    //REMOVE INDEX. Note re-indexing also takes place
    static public function removeIndex(int $index):void                                                                         { $obj = &self::$arrayPages[self::$pageNumber]; unset($obj['nodes'][$index]); self::reIndex();}

    //WITHIN BOUNDARY
    static public function nodeWithinBoundary(array $properties, array $objBoundary):bool                                       { $maxLeft = $properties['left'] + $properties['width'] ; $maxTop  = $properties['top'] + $properties['height'] ; if ($properties['left'] >= $objBoundary['left'] && $maxLeft <= $objBoundary['maxLeft'] && $properties['top'] >= $objBoundary['top'] && $maxTop <= $objBoundary['maxTop']) {return true;} return false;}

   //OVERLAP BOUNDARY
   static public function nodeOverlapsBoundary(array $properties, array $objBoundary):bool                                      { $left =  $properties['left'];  $top  =  $properties['top'];  $maxLeft =  $properties['left'] + $properties['width'];  $maxTop =  $properties['top'] + $properties['height'];  if ($left >  $objBoundary['maxLeft'] ||  $maxLeft < $objBoundary['left']) { return false;} if ($top >  $objBoundary['maxTop']|| $maxTop <= $objBoundary['top']) {return false;} return true; }

    //MERGE NODES. Merges two blocks together (in $arrayPages[$page]['content']) and (by default) applies reIndex(). Note the base-Node will get new dimensions (top, left, height etc...) 
    static public function mergeNodes(int $baseIndex, int $appendIndex, bool $resetIndex = true ):void                          { $obj = &self::$arrayPages[self::$pageNumber]; $objBase =      &$obj['nodes'][$baseIndex];  $objAppend =    &$obj['nodes'][$appendIndex];   if($objBase['tag'] === "text" && $objAppend['tag'] === "text"  ) {  $txt1 = sys::strtoupper($objBase['content']); $txt2 = $objBase['content'];  if($txt1 === $txt2 && sys::length($txt1) > 1) {  $objAppend['content'] = sys::strtoupper($objAppend['content']);   }  /* hyphen connector */ $lastChar = sys::substr(strip_tags($objBase['content']), -1); $newChar  = sys::substr(strip_tags($objAppend['content']),0,1);  if($lastChar === "-" && !self::isUpperCased($newChar)) {$objBase['content'] = rtrim($objBase['content'], "-");  } }  $objBase['content'] .=  $objAppend['content'];    $minLeft =  min([$objBase['left'],$objAppend['left']]);   $minTop  =  min([$objBase['top'],$objAppend['top']]);   /* calc new width */    $finalLeft1 =  $objBase['left'] +  $objBase['width'];  $finalLeft2 =  $objAppend['left'] +  $objAppend['width'];  $maxLeft =     max([$finalLeft1,$finalLeft2]);  /* calc new height */    $finalTop1 = $objBase['top'] + $objBase['height'];    $finalTop2 = $objAppend['top'] + $objAppend['height'];   $maxTop =     max([$finalTop1,$finalTop2]);  $objBase['height'] =   $maxTop - $minTop;     $objBase['left']     =  $minLeft;    $objBase['top']      =  $minTop;     $objBase['width'] = $maxLeft - $minLeft;    unset($obj['nodes'][$appendIndex]);  if($resetIndex) {self::reIndex();}   }

    //COLLECT VALUE->INDEXES as array. Note Value is used as key, but should not be used for calculations because it combines other values based on the margin, and will take the most used value as key.
    static public function collectPropertyValues(array $nodes, string $property, int $margin):array                             { $arrayCollection = [];/* collect items first without any range */ foreach( $nodes as $index => $properties)  {  $value = $properties[$property]; if(!sys::isInt($value)) { continue; }  if(!isset($arrayCollection[$value])){ $arrayCollection[$value]=[]; }  $arrayCollection[$value][]=$index;} /*  apply margin grouping of similar key values  */ ksort($arrayCollection); $result = array();$temp =  array(); foreach ($arrayCollection as $key => $value)  { if (empty($temp))  {    $temp[$key] = $value; }  else  {  end($temp);   $last_key = key($temp);   if ($key - $last_key <= $margin) { $temp[$key] = $value;  } else { $result[] = $temp; $temp = array($key => $value);  }  } } if (!empty($temp)) { $result[] = $temp;}   $arrayCollection = $result; /*  apply merger of grouping grouping */ $arr=[];  foreach ($arrayCollection as $key => $collection)   { $arrayKeys = array_keys($collection); if(sizeof($collection)==1) {    $arr[$arrayKeys[0]] = $collection[$arrayKeys[0]];  } else{ $maxCount = 0;   $maxKey =   0;   $subArr =   [];  foreach ($collection as $key => $subArray)   {  if (count($subArray) > $maxCount)  {   $maxCount = count($subArray); $maxKey = $key;  }  $subArr = array_merge( $subArr , $subArray ); }  $arr[$maxKey] =  $subArr;  } } $arrayCollection = $arr; /* sort by top ASC, left ASC */ $obj = &self::$arrayPages[self::$pageNumber]; foreach ($arrayCollection as $value => $indexes)  { $len = sizeof($indexes); if($len<=1){continue;} $arrTop =   []; $arrLeft =  []; for($n=0;$n<$len;$n++) { $indx = $indexes[$n]; $arrTop[] =  $obj['nodes'][$indx]['top'];   $arrLeft[] = $obj['nodes'][$indx]['left']; } array_multisort($arrTop, SORT_ASC, $arrLeft, SORT_ASC, $indexes); $arrayCollection[$value] = $indexes;} ksort($arrayCollection);return $arrayCollection;  }
    
    //COLLECT Nodes From indexes. Note that the index-numbers themselves are preserved.
    static public function returnNodesFromIndexes(array $indexes ):array                                                       { $obj = &self::$arrayPages[self::$pageNumber];  $out = [];$prop = $obj['nodes']; foreach($prop as $index => $properties) {  if(!in_array($index,$indexes)){continue;}  $out[$index]=$properties;} return $out;  }

    //COLLECT MIN/MAX PROPERY VALUE (of $indexes). Use only for numeric values
    static public function returnMinMaxProperyValue(string $property, array $indexes, bool $isMax=true )                       { $obj = &self::$arrayPages[self::$pageNumber]; $nodes = self::returnNodesFromIndexes($indexes);$out = 0;foreach($nodes as $index => $properties) {  $value = $properties[$property]; if($out == 0) { $out = $value; } if($isMax and $value > $out)    { $out = $value;} if(!$isMax and $value < $out)   { $out = $value;}} return $out;}

    //RETURN NEW GROUPNUMBER
    public static function getNewGroupNumber(): int                                                                             { $obj = &self::$arrayPages[self::$pageNumber]; $groupNumbers = array_column($obj['nodes'], 'groupNumber');return max($groupNumbers) + 1;}

    //CREATE GROUPS from nodes
    static public function groupNodes(array $indexes):bool                                                                      {$obj = &self::$arrayPages[self::$pageNumber];$nodes =   self::returnNodesFromIndexes($indexes);$groups =    self::collectPropertyValues($nodes,"groupNumber",0);$arrayGroupNumbers =    array_values(array_unique(array_keys($groups)));if(!in_array(0,$arrayGroupNumbers)) { return false; } $groupId = max($arrayGroupNumbers); if($groupId == 0) {$groupId = self::getNewGroupNumber();} foreach ($nodes as $index => $properties)  {if($properties['groupNumber'] > 0 ) {continue;} $obj['nodes'][$index]['groupNumber'] = $groupId; } return true;}
    
    //GET ALL ASSIGNED GROUPS-NUMBERS
    static public function returnAssignedGroups():array                                                                         {$obj = &self::$arrayPages[self::$pageNumber]; $groupNumbers = array_map(function($item) { return $item['groupNumber'];}, $obj['nodes']); $groupNumbers = array_filter($groupNumbers, function($number) { return $number > 0;}); $groupNumbers = array_values(array_unique($groupNumbers)); return $groupNumbers;}
    
    //BOUNDARY GROUP DATA. return boundary-data from a given groupnumber
    static public function returnGroupBoundary(int $groupNumber):array                                                          {$obj = &self::$arrayPages[self::$pageNumber];  $block=[];  $block['left']= 0;  $block['top']= 0;  $block['width']= 0;  $block['height']=  0;  $block['maxLeft']= 0;  $block['maxTop']= 0;  $block['pagePercentageStartTop']=   0;  $block['pagePercentageStartLeft']=  0;  $block['pagePercentageEndTop']=     0;  $block['pagePercentageEndLeft']=    0; $nodes = self::returnProperties( "groupNumber", $groupNumber,true );foreach ($nodes as $index => $properties) { $maxLeft =  $properties['left'] +  $properties['width'];  $maxTop =   $properties['top'] +  $properties['height']; if( $block['left'] == 0) { $block['left'] = $properties['left'];} if( $block['top'] == 0) { $block['top'] = $properties['top'];} if( $block['maxLeft'] == 0) { $block['maxLeft'] = $maxLeft;} if( $block['maxTop'] == 0) { $block['maxTop'] =  $maxTop;}  if($properties['left'] < $block['left'] )   {  $block['left'] = $properties['left'];} if($properties['top'] < $block['top'] )     {  $block['top'] = $properties['top'];}  if($maxLeft> $block['maxLeft'] )     {  $block['maxLeft'] = $maxLeft;}  if($maxTop > $block['maxTop'] )     {  $block['maxTop'] = $maxTop;} } $block['width'] =   $block['maxLeft'] - $block['left']; $block['height'] =  $block['maxTop'] - $block['top']; $block['pagePercentageStartTop'] =  round(($block['top'] / $obj['meta']['pageHeight']) * 100,2);   $block['pagePercentageStartLeft'] =     round(($block['left'] / $obj['meta']['pageWidth']) * 100,2);   $block['pagePercentageEndTop'] =        round(( $block['maxTop'] / $obj['meta']['pageHeight']) * 100,2);    $block['pagePercentageEndLeft'] =       round(( $block['maxLeft'] / $obj['meta']['pageWidth']) * 100,2);   return $block; }
    
    //GET ALL INDEXES FROM A GROUP
    static public function returnGroupIndexes(int $groupNumber):array                                                           { $obj = &self::$arrayPages[self::$pageNumber]; $nodes =  self::returnProperties("groupNumber",$groupNumber,true); return array_keys($nodes); }
  
    //TEXT IN UPPERCASE
    static public function isUpperCased($str):bool                                                                               {$txt = sys::strtoupper($str); if($txt === $str) { return true;} return false;}

    //DETERMINE IF TEXTNODES ARE ALLOWED TO BE MERGED
    static public function textNodesAreMergable($node1 , $node2):bool                                                            {if($node1['tag'] !== "text" || $node2['tag'] !== "text" )   { return false; } if($node1['fontSize'] <> $node2['fontSize'] ){ return false; }   /* text-tyles do not match */ if(sys::length($node1['content'])>10 && sys::length($node2['content'])>10)  { if(self::isUpperCased($node1['content']) != self::isUpperCased($node2['content'])) { return false; } }   if(sys::length($node1['content'])>8 && sys::length($node2['content'])>8 && $node1['fontId'] <> $node2['fontId'] )   { if(self::isUpperCased($node1['content']) != self::isUpperCased($node2['content'])) { return false;  }   }    /* check if texts flows correctly */  $lastChar = sys::substr(strip_tags($node1['content']), -1);  $newChar  = sys::substr(strip_tags($node2['content']),0,1); if(sys::isAlpha($lastChar) && sys::isAlpha($newChar)) { if(digi_pdf_to_html::isUpperCased($lastChar) != digi_pdf_to_html::isUpperCased($newChar) ) { return false;  }   if(self::hasClosingHtmlTag($node1['content']))                                             { return false;  } }  return true;   }
   
    //SORT ON PROPERTY Note that the index-numbers themselves are preserved.
    static public function sortNodesByProperty(array $nodes, string $property, bool $isAsc=true):array                           {if($isAsc){  uasort($nodes, function($a, $b) use ($property)  { return $a[$property] <=> $b[$property];});} else {    uasort($nodes, function($a, $b) use ($property) { return $b[$property] <=> $a[$property];}); }  return $nodes;}
    
    //HAS A CLOSING HTML TAG
    static public function  hasClosingHtmlTag($str)                                                                              {return preg_match('/<\/[^\s>]+>$/', $str) === 1;}


    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //CONTENT PARSING
    //#################################################################################
    //#################################################################################
    //#################################################################################
    //#################################################################################
   
    private static function parseContent(): void
    {
        //print_r(self::$arrayPages[digi_pdf_to_html::$pageNumber]);exit;
        
        //----------------------------------------
        //pre-cleaning up. Anything before any merger attempt is performed
        
        new pth_removeInvisibleTexts();
        new pth_removeStrangeTexts();
        new pth_removeStrangeSizedImages();
        new pth_removeBlurredImages();
        new pth_removeNearWhiteImages();
        new pth_removeHeader();
        new pth_removeFooter();
        new pth_removeOverlappingImages();
        new pth_relocateSingleCharacters();
 

        //---------------------------------------
        //text merger (preserve sequence!!!!!!!) before any grouping attempt is performed

        new pth_floatingTexts();
        new pth_leftAlignedTexts();
        new pth_rightAlignedTexts(); //must come after left alignment
        new pth_centeredTexts();
        new pth_capitalStartLetter();
        new pth_textColumns(); //replacement version
        new pth_textColumnsPostBlockWithImage();
        new pth_textColumnsPreBlockWithTitle(); 
        new pth_textColumnsPreBlockWithImage();
        

        new pth_multiLineHeaders();
        
        //--------------------------------------
        //grouping(1) (assigning ungrouped items into groups) - with all ungrouped nodes
        new pth_leftAlignedNodes();
        new pth_ungroupedTextHeaderAboveUngroupedText();
        new pth_centeredNodes();
        new pth_ungroupedTextWithinOtherUngroupedText();
       
        //--------------------------------------
        //grouping(2) (assigning ungrouped items into groups) - having grouped nodes
        new pth_ungroupedTextWithinGroupedBoundary();
        new pth_ungroupedImageWithinGroupedBoundary();
        new pth_ungroupedTextOverlapGroupedBoundary();
        new pth_ungroupedTextHeaderAboveGroupedBoundary();    
        new pth_ungroupedImageBelowGroupedBoundary();
        new pth_ungroupedImageAboveGroupedBoundary();
        new pth_ungroupedImageLeftFromGroupedBoundary();
        new pth_ungroupedImageOverlapGroupedBoundary();

        
        //-------------------------------------
        //post grouping (re-aranging items within groups)
        new pth_groupedSetDefaultSequence();
        new pth_groupedNodesLeftAlignedSequence();
        new pth_groupedNodesTopSequence();  //must come adter LeftAlignedSequence()
        new pth_sortGroupsFromLeft();      //group the groups themselves from the left

        //-------------------------------------
        //post cleanup 
        new pth_ungroupedInvisibleTexts();
        new pth_trimImagesOutOfBoundary();


       

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
