<?php
declare(strict_types=1);
//###############################################################
class html_parser
{
	private $dom;
	private $utf8Encoder;
	private $arrayComments=array();
	
	public function __construct()
    {	
		$this->dom = 			new domDocument;
		$this->utf8Encoder=		chr(60).chr(63)."xml version=\"1.0\" encoding=\"UTF-8\"".chr(63).chr(62);
		$this->arrayComments['key']=array();
		$this->arrayComments['str']=array();

	} 
	
	//####################
	
	//general
	public function tagName($tagName,$baseElem=null)				{if($baseElem){return $baseElem->getElementsByTagName($tagName);}else{return $this->dom->getElementsByTagName($tagName);}}	
	public function id($name)										{return $this->dom->getElementById($name);}	
	public function hasId($name)									{if($this->dom->getElementById($name)){return true;}else{return false;}}		
	public function attribute($name,$baseElem=null)					/* note:return regular array so use sizeof instead of ->length  */ {$arr=array();if($baseElem){foreach($baseElem->getElementsByTagName('*') as $element ){if($this->hasAttribute($element,$name)){array_push($arr,$element);}}}else{foreach($this->dom->getElementsByTagName('*') as $element ){if($this->hasAttribute($element,$name)){array_push($arr,$element);}}}return $arr;}
	public function hasName($name)									{foreach ($this->dom->getElementsByTagName('*') as $element){if($this->hasAttribute($element,"name")){if($this->getAttribute($element,"name") === $name){return true;}}}return false;}
	public function name($name)										{foreach ($this->dom->getElementsByTagName('*') as $element){if($this->hasAttribute($element,"name")){if($this->getAttribute($element,"name") === $name){return $element;}}}return null;}
	public function cloneNode($elem) 								{return $elem->cloneNode(true);}

	//content related
	public function setFullHtml(string $str)						{$str = $this->removeComments($str); if(strstr($str,"<![CDATA")){ $str = $this->removeCData($str); } @$this->dom->loadHTML($this->utf8Encoder.$str);}    //DOMDocument::loadHtml() converts all elements to lowercase; also add '@' because  HTML5 header and footer tags will else cause error
	public function returnHtml()									{$html = $this->dom->saveHTML(); $html = str_ireplace($this->utf8Encoder,"",$html); $html = $this->restoreComments($html); return $this->removeOddChars($html);}
	public function innerHTML($elem)								{$innerHTML = ""; if(!isset($elem)){return "";} $children  = $elem->childNodes;foreach ($children as $child){$innerHTML .= $elem->ownerDocument->saveHTML($child);}  $innerHTML=$this->restoreComments($innerHTML); return $this->removeOddChars($innerHTML);}
	public function outerHTML($elem)								{if(!isset($elem)){return "";} $outerHTML = $this->dom->saveHTML($elem); $outerHTML=$this->restoreComments($outerHTML); return $outerHTML; }
	public function setTextContent($elem,$str)						{$elem->textContent = "";$x = $this->createTextNode($str);$this->appendLast($elem,$x);}
	public function setInnerHTML($elem, $html)						{if(sys::length($html)==0){$html=" ";} $html = $this->removeComments($html); $html = $this->returnHtmlToXml($html);$fragment = $elem->ownerDocument->createDocumentFragment();$fragment->appendXML($html);while ($elem->hasChildNodes()){$elem->removeChild($elem->firstChild);}$elem->appendChild($fragment);}

	//attributes
	public function getAttributes($elem)							{$array = array();$array['style']=array(); if(!$elem){return $array;} if($elem->hasAttributes()){foreach ($elem->attributes as $attr) {$name = sys::trim($attr->nodeName);$value = sys::trim($attr->nodeValue);if(sys::strtolower($name) === 'style'){$arr=explode(';',$value);$loop=sizeof($arr);for($n=0;$n<$loop;$n++){$line = explode(':',$arr[$n]);if(sys::length($line[0])>0){$line[0]=sys::strtolower(sys::trim($line[0]));$line[1]=sys::strtolower(sys::trim($line[1]));$array['style'][$line[0]]=$line[1];}}}else{$array[$name]=$value;}}}return $array;}
	public function getAttribute($elem,$name)						{$out='';if($this->hasAttribute($elem,$name)){$out=$elem->getAttribute($name);}return $this->removeOddChars($out);}
	public function hasAttribute($elem,$name)						{if($elem->hasAttribute($name)) {return true;}else{return false;}}
	public function setAttribute($elem,$name,$value)				{if(sys::strtolower($name)==='style'){$name="style";}$elem->setAttribute($name,$value);}
	public function removeAttribute($elem,$name)					{if($this->hasAttribute($elem,$name)){$elem->removeAttribute($name);}}
	
	//css related
	public function setCssProperty($elem,$name,$value)				{$name=sys::trim($name);$value=sys::trim($value);$name=sys::strtolower($name);$arr = $this->getAttributes($elem);$arr['style'][$name]=$value;$arr_out=array();foreach ($arr['style'] as $key => $value){$str=$key.": ".$value;array_push($arr_out,$str);}if(sizeof($arr_out)>0){$this->setAttribute($elem,"style",implode(";",$arr_out));}else{$this->removeAttribute($elem,"style");}}
	public function removeCssProperty($elem,$name)					{$name=sys::trim($name);$name=sys::strtolower($name);$arr = $this->getAttributes($elem);if(isset($arr['style'][$name])){unset($arr['style'][$name]);}$arr_out=array();foreach ($arr['style'] as $key => $value){$str=$key.": ".$value;array_push($arr_out,$str);}if(sizeof($arr_out)>0){$this->setAttribute($elem,"style",implode(";",$arr_out));}else{$this->removeAttribute($elem,"style");}}
	public function hasInitialproperty($elem,$name)					{$name=sys::trim($name);$name=sys::strtolower($name);$arr = $this->getAttributes($elem);if(isset($arr['style'][$name])){return true;}else{return false;}}
	public function returnInitialproperty($elem,$name)				{$name=sys::trim($name);$name=sys::strtolower($name);$arr = $this->getAttributes($elem);if(isset($arr['style'][$name])){return $arr['style'][$name];}else{return '';}}
	public function addClass($elem,$name)							{if(!$this->hasAttribute($elem,"class")){$this->setAttribute($elem,"class","");}$classList = $this->getAttribute($elem,"class");$classList = trim(sys::removeMultiSpaces($classList));if(sys::length($classList)==0){$arrClassList=array();}else{$arrClassList = explode(" ",$classList);}if(in_array($name,$arrClassList)){return;}else{array_push($arrClassList,$name);}$this->setAttribute($elem,"class",implode(" ",$arrClassList));}	
	public function hasClass($elem,$name)							{if(!$this->hasAttribute($elem,"class")){return false;}$classList = $this->getAttribute($elem,"class");$classList = trim(sys::removeMultiSpaces($classList));if(sys::length($classList)==0){return false;}else{$arrClassList = explode(" ",$classList);if(in_array($name,$arrClassList)){return true;}}return false;}
	public function removeClass($elem,$name)						{if(!$this->hasClass($elem,$name)){return;}$arrClassList = explode(" ",$this->getAttribute($elem,"class"));$index = array_search($name,$arrClassList);unset($arrClassList[$index]);$arrClassList=array_values($arrClassList);if(sizeof($arrClassList)>0){$this->setAttribute($elem,"class",implode(" ",$arrClassList));}else{$this->removeAttribute($elem,"class");}}
	
	//dom related
	public function returnNodeName($elem,$toLower=true)				{$node =  sys::trim($elem->nodeName); if($toLower){ $node = strtolower($node);} return $node;}
	public function parentNode($elem)								{return $elem->parentNode;}
	public function isTextNode($elem)								{if(sys::posInt($elem->nodeType) == 3){return true;}else{return false;}}
	public function createTextNode($txt)							{return $this->dom->createTextNode($txt);}
	public function replaceNode($src,$tgt)							{$src->parentNode->replaceChild($tgt,$src);}
	public function createElem($tagname)							{return $this->dom->createElement($tagname);}
	public function appendLast($src,$tgt)							{$src->appendChild($tgt);}
	public function appendBefore($src,$tgt)							{$src->parentNode->insertBefore($tgt,$src);}
	public function appendFirst($parent,$child)						{if(!$parent->childNodes){$this->appendLast($parent,$child);return;}$parent->insertBefore($child,$parent->childNodes[0]);}
	public function appendAfter($elem_ref,$elem_new)				{$elem_ref->parentNode->insertBefore($elem_new,$elem_ref->nextSibling);}	
	public function removeNode($elem)								{$elem->parentNode->removeChild($elem);}
	public function returnAllNodes($elem,&$array=array())			{foreach($elem->childNodes as $node){array_push($array,$node);if($node->hasChildNodes()){$this->returnAllNodes($node,$array);}}return $array;}
	public function returnParentnode($elem,$name)					{$tagname="";while($elem){if($elem->parentNode){$elem = $elem->parentNode;if($this->isTextNode($elem)){return false;}$nodeName = strtolower($elem->nodeName);if($nodeName === 'body' or $nodeName === 'html'){return false;}if($name === $nodeName) {return $elem; break;}}else{return false;}}return false;}
	public function returnFirstChildren($baseElem,$tagName)			{if(!$baseElem){sys::error("Usage of flatTagName() required a valid base element (must be within body tag)");exit;}$arrayNodes=array();$nodes = $this->tagName($tagName,$baseElem);if($nodes->length==0){return $arrayNodes;}$node = $nodes[0];array_push($arrayNodes,$node);while($node){$node = $node->nextSibling;if($node){if($node->nodeType == 1){if($node->nodeName === $tagName ){array_push($arrayNodes,$node);}}}}return $arrayNodes;} 

	//search related
	public function returnFirstClassNameNode($name,$baseElem=null)	{if($baseElem){ $nodes= $baseElem->getElementsByTagName('*');}else{$nodes= $this->dom->getElementsByTagName('*');}$len =  $nodes->length;for($i=0;$i<$len;$i++){if($this->hasClass($nodes[$i],$name)){return $nodes[$i];}}return null;}	
	public function returnClassNameNodes($name,$baseElem=null)		{$arr=array();if($baseElem){ $nodes= $baseElem->getElementsByTagName('*');}else{$nodes= $this->dom->getElementsByTagName('*');}$len =  $nodes->length;for($i=0;$i<$len;$i++){if($this->hasClass($nodes[$i],$name)){array_push($arr,$nodes[$i]);}}return $arr;}	


	//##############################################################################
	//##############################################################################
	//##############################################################################
	//VARIOUS FUNCTIONS AND PARSERS
	//##############################################################################
	//##############################################################################
	//##############################################################################
	public function extractHexColor($str)							{$str=" ".$str;while(strpos($str,"#",0)){$pos = strpos($str,"#",0);$color=	strtolower(substr($str, $pos,7));if(preg_match('/^#[a-f0-9]{6}$/i',$color)){return $color;}}return '';}
	public function returnPxValues($str)							{$pattern = "/\b\d{1,4}(?:\.\d{1,4})?px/";preg_match_all($pattern, $str, $matches) ? $matches[0] : 'fail';if($matches[0]){$matches[0] = array_map('intval',$matches[0]); return $matches[0];}else{return array();}}
	private function setPropVal(&$prop,$val)						{if( ($prop === 0 or strlen($prop)==0) and strlen($val)>0){$prop=trim($val);}}
	private function removeComments($str)							{if (preg_match_all('#<\!--(.*)-->#Uis', $str, $rcomments)){foreach ($rcomments[0] as $c) {$key = "yesKey_".md5($c); $keyStr = 	 "<!-- ".$key." -->";if(!in_array($keyStr,$this->arrayComments['key']) and !stristr($c,"yesKey_")){array_push($this->arrayComments['key'],$keyStr);array_push($this->arrayComments['str'],$c);}}$str = str_replace($this->arrayComments['str'],$this->arrayComments['key'],$str);}return $str;} /* example <!--StartFragment--><!--EndFragment--> */
	private function restoreComments($html)							{$html = str_replace($this->arrayComments['key'],$this->arrayComments['str'],$html);return $html;}
	private function removeOddChars($str)							{$str = str_ireplace(sys::chr(194),"",$str);$str = str_ireplace(sys::chr(160)," ",$str);$str = str_ireplace(array("%5B","%5D"),array("[","]"),$str);return $str;}
	public function removeCData($string)							{return preg_replace_callback('~<!\[CDATA\[(.*)\]\]>~',function (array $matches) {return htmlspecialchars($matches[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');},$string);}
	public function deleteComments($html)							{return preg_replace('/<!--(.|\s)*?-->/', '', $html);}
	//##############################################################################
	
	public function returnCellProperties($elem)
	{
		$array=array();
		
		$array['border-color']="";
		$array['background-color']="";
		
		$array['border']=array();
		$array['border']['t']=0;	
		$array['border']['r']=0;
		$array['border']['b']=0;
		$array['border']['l']=0;
		
		$array['padding']=array();
		$array['padding']['t']=0;	
		$array['padding']['r']=0;
		$array['padding']['b']=0;
		$array['padding']['l']=0;
		//------			
	 	if($this->hasInitialproperty($elem,'border-width'))
		{			
				$css = 		$this->returnInitialproperty($elem,'border-width');
				$vals = 	$this->returnPxValues($css); 
				if(sizeof($vals)==3) //top | vertical | bottom 
				{
					$this->setPropVal($array['border']['t'],$vals[0]);
					$this->setPropVal($array['border']['r'],$vals[1]);
					$this->setPropVal($array['border']['b'],$vals[2]);	
					$this->setPropVal($array['border']['l'],$vals[1]);	
				}
				if(sizeof($vals)==4)//top | right | bottom | left 
				{
					$this->setPropVal($array['border']['t'],$vals[0]);	
					$this->setPropVal($array['border']['r'],$vals[1]);
					$this->setPropVal($array['border']['b'],$vals[2]);
					$this->setPropVal($array['border']['l'],$vals[3]);
				}
				if(sizeof($vals)==2) //horizontal | vertical | 
				{
					$this->setPropVal($array['border']['t'],$vals[0]);
					$this->setPropVal($array['border']['r'],$vals[1]);
					$this->setPropVal($array['border']['b'],$vals[0]);	
					$this->setPropVal($array['border']['l'],$vals[1]);	
				}
				if(sizeof($vals)==1)
				{
					$this->setPropVal($array['border']['t'],$vals[0]);
					$this->setPropVal($array['border']['r'],$vals[0]);
					$this->setPropVal($array['border']['b'],$vals[0]);	
					$this->setPropVal($array['border']['l'],$vals[0]);	
				}	
		}	
		//----
		if($this->hasInitialproperty($elem,'border'))
		{			
				$css = 		$this->returnInitialproperty($elem,'border');
				$this->setPropVal($array['border-color'],$this->extractHexColor($css));
				$vals = 	$this->returnPxValues($css); 
				if(sizeof($vals)==3) //top | vertical | bottom 
				{
					$this->setPropVal($array['border']['t'],$vals[0]);
					$this->setPropVal($array['border']['r'],$vals[1]);
					$this->setPropVal($array['border']['b'],$vals[2]);	
					$this->setPropVal($array['border']['l'],$vals[1]);	
				}
				if(sizeof($vals)==4)//top | right | bottom | left 
				{
					$this->setPropVal($array['border']['t'],$vals[0]);	
					$this->setPropVal($array['border']['r'],$vals[1]);
					$this->setPropVal($array['border']['b'],$vals[2]);
					$this->setPropVal($array['border']['l'],$vals[3]);
				}
				if(sizeof($vals)==2) //horizontal | vertical | 
				{
					$this->setPropVal($array['border']['t'],$vals[0]);
					$this->setPropVal($array['border']['r'],$vals[1]);
					$this->setPropVal($array['border']['b'],$vals[0]);	
					$this->setPropVal($array['border']['l'],$vals[1]);	
				}
				if(sizeof($vals)==1)
				{
					$this->setPropVal($array['border']['t'],$vals[0]);
					$this->setPropVal($array['border']['r'],$vals[0]);
					$this->setPropVal($array['border']['b'],$vals[0]);	
					$this->setPropVal($array['border']['l'],$vals[0]);	
				}	
		}	
		//----
		if($this->hasInitialproperty($elem,'border-top'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-top');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
			$this->setPropVal($array['border']['t'],$vals[0]);
		}
		if($this->hasInitialproperty($elem,'border-right'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-right');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
			$this->setPropVal($array['border']['r'],$vals[0]);
		}
		if($this->hasInitialproperty($elem,'border-bottom'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-bottom');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
			$this->setPropVal($array['border']['b'],$vals[0]);
		}
		if($this->hasInitialproperty($elem,'border-left'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-left');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
			$this->setPropVal($array['border']['l'],$vals[0]);
		}
		//---		
		if($this->hasInitialproperty($elem,'border-color'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-color'); 
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
		}	
		//---
		//[2021-03-04]
		if($this->hasInitialproperty($elem,'border-right-width'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-right-width');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border']['r'],$vals[0]);
		}
		if($this->hasInitialproperty($elem,'border-bottom-width'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-bottom-width');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border']['b'],$vals[0]);
		}
		if($this->hasInitialproperty($elem,'border-left-width'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-left-width');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border']['l'],$vals[0]);
		}
		if($this->hasInitialproperty($elem,'border-top-width'))
		{
			$css = 					$this->returnInitialproperty($elem,'border-top-width');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['border']['t'],$vals[0]);
		}
		//------------------------------------------------------------------------
		if($this->hasInitialproperty($elem,'padding'))
		{
			$css = 					$this->returnInitialproperty($elem,'padding');
			$vals = 				$this->returnPxValues($css);  
			if(sizeof($vals)==3) //top | vertical | bottom 
			{
				$this->setPropVal($array['padding']['t'],$vals[0]);
				$this->setPropVal($array['padding']['r'],$vals[1]);
				$this->setPropVal($array['padding']['b'],$vals[2]);	
				$this->setPropVal($array['padding']['l'],$vals[1]);	
			}
			if(sizeof($vals)==4)//top | right | bottom | left 
			{
				$this->setPropVal($array['padding']['t'],$vals[0]);	
				$this->setPropVal($array['padding']['r'],$vals[1]);
				$this->setPropVal($array['padding']['b'],$vals[2]);
				$this->setPropVal($array['padding']['l'],$vals[3]);
			}
			if(sizeof($vals)==2) //horizontal | vertical | 
			{
				$this->setPropVal($array['padding']['t'],$vals[0]);
				$this->setPropVal($array['padding']['r'],$vals[1]);
				$this->setPropVal($array['padding']['b'],$vals[0]);	
				$this->setPropVal($array['padding']['l'],$vals[1]);	
			}
			if(sizeof($vals)==1)
			{
				$this->setPropVal($array['padding']['t'],$vals[0]);
				$this->setPropVal($array['padding']['r'],$vals[0]);
				$this->setPropVal($array['padding']['b'],$vals[0]);	
				$this->setPropVal($array['padding']['l'],$vals[0]);	
			}		 
		}		
		//------
		if($this->hasInitialproperty($elem,'padding-top'))
		{
			$css = 					$this->returnInitialproperty($elem,'padding-top');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['padding']['t'],$vals[0]);
		}		
		if($this->hasInitialproperty($elem,'padding-right'))
		{
			$css = 					$this->returnInitialproperty($elem,'padding-right');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['padding']['r'],$vals[0]);
		}		
		if($this->hasInitialproperty($elem,'padding-bottom'))
		{
			$css = 					$this->returnInitialproperty($elem,'padding-bottom');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['padding']['b'],$vals[0]);
		}
		if($this->hasInitialproperty($elem,'padding-left'))
		{
			$css = 					$this->returnInitialproperty($elem,'padding-left');
			$vals = 				$this->returnPxValues($css); 
			$this->setPropVal($array['padding']['l'],$vals[0]);
		}
		//------
		if($this->hasInitialproperty($elem,'background-color'))
		{
			$css = 	$this->returnInitialproperty($elem,'background-color'); 
			$this->setPropVal($array['background-color'],$this->extractHexColor($css));
		}
		//-----	
		//[2021-03-04] border coloring (can only be one color per cell)
		if($this->hasInitialproperty($elem,'border-left-color'))
		{
			$css = $this->returnInitialproperty($elem,'border-left-color');
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
		}
		if($this->hasInitialproperty($elem,'border-top-color'))
		{
			$css = $this->returnInitialproperty($elem,'border-top-color');
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
		}
		if($this->hasInitialproperty($elem,'border-right-color'))
		{
			$css = $this->returnInitialproperty($elem,'border-right-color');
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
		}
		if($this->hasInitialproperty($elem,'border-bottom-color'))
		{
			$css = $this->returnInitialproperty($elem,'border-bottom-color');
			$this->setPropVal($array['border-color'],$this->extractHexColor($css));
		}

		if(strlen($array['border-color'])==0)
		{
			$array['border']['t']=0;	
			$array['border']['r']=0;
			$array['border']['b']=0;
			$array['border']['l']=0;			
		}
		//------
		return $array;	
	}	
	
	//##############################################################################
	
	public function returnTableProperties($elem)
	{
		$array=array();
		$array['border-color']="";
		$array['background-color']="";	
		$array['border-width']=0;	
	 	if($this->hasInitialproperty($elem,'border-width'))
		{
				$css = 		$this->returnInitialproperty($elem,'border-width');
				$vals = 	$this->returnPxValues($css); 
				$this->setPropVal($array['border-width'],$vals[0]);	 
		}

		if($this->hasInitialproperty($elem,'border-color'))
		{
				$css = 		$this->returnInitialproperty($elem,'border-color');
				$vals = 	$this->returnPxValues($css); 
				$this->setPropVal($array['border-color'],$this->extractHexColor($css));  
		}

		if($this->hasInitialproperty($elem,'background-color'))
		{
				$css = 	$this->returnInitialproperty($elem,'background-color'); 
				$this->setPropVal($array['background-color'],$this->extractHexColor($css));
		}

		if($this->hasInitialproperty($elem,'border'))
		{
				$css = 	$this->returnInitialproperty($elem,'border');
				$vals = $this->returnPxValues($css); 
				$this->setPropVal($array['border-width'],$vals[0]);	
				$this->setPropVal($array['border-color'],$this->extractHexColor($css)); 
				
		}		
		
		return $array;
		
	}	
	//##############################################################################
	private function returnTags($tagName,$html)				{preg_match_all('/<'.$tagName.'*[^>]+>/i',$html, $results);return $results[0];}
	private function returnHtmlToXml($html)					
	{
		$arraySelfClosingTags=array("input","img");
		$loop=sizeof($arraySelfClosingTags);

		for($n=0;$n<$loop;$n++)
		{
			$arr=$this->returnTags($arraySelfClosingTags[$n],$html);
			$loop2=sizeof($arr);
			for($i=0;$i<$loop2;$i++)
			{
				if(stristr($arr[$i],"/>")){continue;}
				$strNew=str_replace(">"," />",$arr[$i]);
				$html = str_replace($arr[$i],$strNew,$html);
			}
		}
		$html=str_ireplace("<br>","<br />",$html);
		$html=str_ireplace("<hr>","<hr />",$html);
		return $html;
	}
	//##############################################################################

}


?>