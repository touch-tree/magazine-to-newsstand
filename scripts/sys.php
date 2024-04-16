<?php
declare(strict_types=1);
//#########################################################
/*
    use for functions with Scalar types finally since PHP7:

    (int) - cast to int
    (bool) - cast to bool
    (float) - cast to float
    (string) - cast to string
    (array) - cast to array
    (object) - cast to object
    (unset) - cast to NULL


*/
//############################################################################
class sys 
{
    static public   $arrayParams =      [];
    static public   $arrayRequests =    [];
    static public   $method =           [];
    static public   $arrayHeaders =     [];
    static public   $arraySessions =    [];

    static private  $errorMessage =     "";
    static private  $reponseCode=       0;

	static public function init()
    {	
        ini_set('session.gc_maxlifetime', "57600");
		ini_set('session.gc_probability', "1");
		ini_set('session.gc_divisor', "100");
		ini_set("session.cookie_lifetime","0");
		session_set_cookie_params(57600);	
		ini_set("register_globals", "0");
		ini_set("magic_quotes_gpc","1");
		ini_set("default_socket_timeout", "4");
		session_cache_limiter("nocache");
        ini_set("display_errors","1");


        //------------
        //set properties
        self::$arrayParams['charSet']=			    "UTF-8";
        self::$arrayParams['fontColor']=            "#555555";
        self::$arrayParams['arrayLanguages']=	    array("en","nl");
        self::$arrayParams['hasMbstring']=		    true;  if(function_exists("mb_strtoupper")){mb_internal_encoding(self::$arrayParams['charSet']);}else{self::$arrayParams['hasMbstring']=false;}
        self::$arrayParams['ipAddress']=		    self::returnIp();   
        self::$arrayParams['userAgent']=		    $_SERVER['HTTP_USER_AGENT'];
        self::$arrayParams['rootFolder']=		    dirname(dirname(dirname(__FILE__)));
        self::$arrayParams['languageCode']=		    "en";
        self::$arrayParams['rootWeb']=			    self::returnRootWeb();
		self::$arrayParams['systemTimeZone']=	    date_default_timezone_get();//"Europe/Amsterdam";
		self::$arrayParams['defaultFontFamily']=    "Tahoma";//Tahoma;	
        self::$arrayParams['output']=               "json"; //options: json, xml, php(as native array, but a string)
        self::$arrayParams['sessionsInitiated']=    false;
        self::$arrayParams['transactionLevel']=     0;      //for databases nested transactions
        self::$arrayParams['databaseFilesFolder']=  null;   //temporary overrule-folder for the database-files. (default is connection::$databaseName)
        self::$arrayParams['OS']=                   strtoupper(PHP_OS_FAMILY); //'Windows', 'BSD', 'Darwin', 'Solaris', 'Linux' , 'OSX'
       

        //------------
        //requests
        self::$method=				                strtoupper($_SERVER['REQUEST_METHOD']);
        self::$arrayRequests=                       self::returnRequests();
        self::$arrayHeaders=                        self::returnHeaders(); //note: keys all lowercase
        
        //------------
        //autostarts
        self::setMemory(512);
        self::setTimeout(60);
     

    }

    //############################################################################
    //unicode replacement functions
    static public function strtoupper()	                {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_strtoupper",func_get_args());}else{return call_user_func_array("strtoupper",func_get_args());}}
    static public function strtolower()			        {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_strtolower",func_get_args());}else{return call_user_func_array("strtolower",func_get_args());}}	
	static public function strlen()			            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_strlen",func_get_args());}else{return call_user_func_array("strlen",func_get_args());}}	
	static public function substr()			            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_substr",func_get_args());}else{return call_user_func_array("substr",func_get_args());}}	
	static public function substr_count()	            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_substr_count",func_get_args());}else{return call_user_func_array("substr_count",func_get_args());}}	
	static public function strstr()			            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_strstr",func_get_args());}else{return call_user_func_array("strstr",func_get_args());}}	
	static public function strrpos()			        {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_strrpos",func_get_args());}else{return call_user_func_array("strrpos",func_get_args());}}		
	static public function strripos()		            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_strripos",func_get_args());}else{return call_user_func_array("strripos",func_get_args());}}	
	static public function strpos()			            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_strpos",func_get_args());}else{return call_user_func_array("strpos",func_get_args());}}	
	static public function stristr()			        {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_stristr",func_get_args());}else{return call_user_func_array("stristr",func_get_args());}}
	static public function stripos()			        {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_stripos",func_get_args());}else{return call_user_func_array("stripos",func_get_args());}}
	static public function split()			            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_split",func_get_args());}else{return call_user_func_array("split",func_get_args());}}	
	static public function parse_str()		            {if(self::$arrayParams['hasMbstring']){return call_user_func_array("mb_parse_str",func_get_args());}else{return call_user_func_array("parse_str",func_get_args());}}
	static public function ucfirst(string $str)	        {if(self::$arrayParams['hasMbstring']){return mb_strtoupper(mb_substr($str,0,1)).mb_substr($str,1);}else{return ucfirst($str);}}
	static public function chr($intval)		            {if(self::$arrayParams['hasMbstring']){return mb_convert_encoding(pack('n',$intval),self::$arrayParams['charSet'],'UTF-16BE');}else{return chr($intval);}}
	static public function strrev(string $str)          {if(self::$arrayParams['hasMbstring']){preg_match_all('/./us', $str, $ar);return join('',array_reverse($ar[0]));}else{return strrev($str);}}
	static public function ord(string $str)		        {if(self::$arrayParams['hasMbstring']){$str = mb_convert_encoding($str,"UCS-4BE",self::$arrayParams['charSet']); $ords = array();for($i = 0; $i < mb_strlen($str,"UCS-4BE"); $i++){$s2 = mb_substr($str,$i,1,"UCS-4BE");$val = unpack("N",$s2); $ords[] = $val[1];} return ($ords[0]);} else{return ord($str);}}
	static public function ucwords(string $str)	        {if(self::$arrayParams['hasMbstring']){return mb_convert_case($str, MB_CASE_TITLE);}else{return ucwords($str);}}
	static public function trim(string $str)	        {$str = preg_replace('/[^\PC\s]/u', '', $str); $str = str_ireplace(self::chr(160)," ",$str); return trim(trim($str,"\x0..\x1f"));}
   
    //############################################################################
    //regular functions
    static public function length($str):int                 { $str = (string)$str; return self::strlen(self::trim($str));}

    //############################################################################
    //json and arrays (note: toJson() if an arrayKey has no array data, then only the parent arrayKey remains, not the sub-keys)
    static public function isValidJson(string $str):bool	{if(!is_string($str)){return false;} $str=self::trim($str); if(self::length($str)==0){return false;} json_decode($str);if(json_last_error() === JSON_ERROR_NONE or json_last_error() == 0){return true;}else{return false;}}
    static public function toJson(array $array):string		{self::castNumericValues($array); self::nullifyEmptyArrays($array); self::$errorMessage = "";$array = self::array2JsonFormat($array);if(strlen(self::$errorMessage)>0){self::error(self::$errorMessage); }return json_encode($array,JSON_PRETTY_PRINT);}
	static public function fromJson(string $json):array	    {$json=self::trim($json); if(self::isValidJson($json)){$array = json_decode($json,true);} else{self::error("Invalid json received");}$array = self::jsonFormat2Array($array);return $array;}

    //############################################################################
    //various functions
    static public function setMemory(int $mb)      { $curr_mem=intval(ini_get('memory_limit'));$mb=intval($mb);if($mb>$curr_mem){ini_set("memory_limit",$mb."M");}}
	static public function isSsl():bool			   { $is_ssl=false;if((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on") or $_SERVER['SERVER_PORT'] == 443){$is_ssl=true;}elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])){if(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'])==='https'){$is_ssl=true;}}return $is_ssl;}
    static public function databaseDir():string    { $dbFolder = connection::$databaseName;if(isset(self::$arrayParams['databaseFilesFolder'])){$dbFolder = self::$arrayParams['databaseFilesFolder'];} return $dbFolder;}
    static public function lockPage()			   { ignore_user_abort(true); }
    static public function setTimeout(int $min)	   { set_time_limit($min * 60);}

    //###########################################################################
    //ip-address functions
	static private function allowIp(string $ip):bool	{if(filter_var($ip, FILTER_VALIDATE_IP) and !self::isLocalIp($ip)){return true;}else{return false;}}
    static private function returnIp():string			{$connectip = ""; $realip = ""; $client  = ""; $forward = ""; $remote  = $_SERVER['REMOTE_ADDR']; $xforward= ""; if(isset($_SERVER['HTTP_CF_CONNECTING_IP']))	{$connectip  = $_SERVER['HTTP_CF_CONNECTING_IP'];} if(isset($_SERVER['HTTP_X_REAL_IP']))			{$realip  =   $_SERVER['HTTP_X_REAL_IP'];} if(isset($_SERVER['HTTP_CLIENT_IP']))			{$client  =   $_SERVER['HTTP_CLIENT_IP'];} if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))		{$forward  =  $_SERVER['HTTP_X_FORWARDED_FOR'];if(stristr($forward,",")){$forward = explode(",",$forward);$forward = trim($forward[0]);}} if(isset($_SERVER['X_FORWARDED_FOR']))			{$xforward  = $_SERVER['X_FORWARDED_FOR'];if(stristr($xforward,",")){$xforward = explode(",",$xforward);$xforward = trim($xforward[0]);}} if(self::allowIp($connectip))					{$ip = $connectip;} elseif(self::allowIp($client))					{$ip = $client;} elseif(self::allowIp($realip))					{$ip = $realip;} elseif(self::allowIp($forward))				    {$ip = $forward;} elseif(self::allowIp($xforward))				{$ip = $xforward;} else {$ip = $remote;} return $ip;}
    static public function isIp(string $ip):bool        {if(self::isIpv4($ip)){return true;} if(self::isIpv6($ip)){return true;}return false;}	
    //############################################################################
    //requests (get, post, ajax), header and cookie-stuff
    static private function returnRequests():array                  { if(self::$method==="GET"){self::$arrayRequests = $_GET;}else{$json = file_get_contents('php://input');if(self::isValidJson($json)){self::$arrayRequests = self::fromJson($json);}else{$pairs = explode("&", file_get_contents("php://input"));$vars = array();foreach ($pairs as $pair) {$nv = explode("=",$pair);$name = urldecode($nv[0]);if(strlen($name)>0){$value = urldecode($nv[1]);$vars[$name] = $value;}}self::$arrayRequests = $vars; if(self::$method === "POST"){foreach ($_POST as $key => $value){if(self::length($key)>0 and !isset($vars[$key])){if(sys::length($value)<500){$vars[$key]=$value;}}}}self::$arrayRequests=$vars;}} foreach (self::$arrayRequests as $key => $value) {if(is_string($value)) {self::$arrayRequests[$key]=self::trim($value);} } self::$arrayRequests=array_merge(self::$arrayRequests,$_GET); /* incase mixed post and get together */ self::castNumericValues(self::$arrayRequests);return self::$arrayRequests;}
    static public function emptyRequests()                          { self::$arrayRequests=array();}
    static public function setVar($key,$value)	                    { self::$arrayRequests[$key]=$value;}
    static public function unsetVar($key)		                    { if(isset(self::$arrayRequests[$key])){unset(self::$arrayRequests[$key]);}}
    static public function queryStringToArray(string $str)          { parse_str($str, $arr); return $arr;}
    static public function setHeader($key, $value)                  { header($key.": ".$value); self::$arrayHeaders[$key]=$value;}
    //############################################################################
    //headers
    static private function returnHeaders():array                   {$arr=array();foreach(getallheaders() as $name => $value){$arr[$name]=$value;}  $arr=array_change_key_case($arr, CASE_LOWER); return $arr; }
    static private function hasHeader($key):bool			        {$key = strtolower($key);if(isset(self::$arrayHeaders[$key])){return true;}else{return false;}}
	static private function returnHeader($key):string			    {$key = strtolower($key);$out="";if(self::hasHeader($key)){$out = self::$arrayHeaders[$key]; }return $out;}
    static public function setReponseCode(int $num)				    {self::$reponseCode = $num; http_response_code($num);}
	static public function compressString($str):string	            {$str=(string) $str; $str = self::clearVar($str); $str=(string) $str; $str=self::strtoupper($str);$str=preg_replace("/[^\p{L}\p{N}]/iu","",$str);return $str;}	
    static public function clearVar($str)		                    {$str=(string) $str; $str = self::trim($str);     $str = strip_tags($str); $str = str_ireplace("|","", $str); $len = strlen($str); if($len>1048576){self::error("Attempt to overflow data during a submission. Page aborted.");} $str = addslashes(self::trim($str));if(is_numeric($str) && !preg_match('/^0\d+/', (string)$str)) {$str = $str + 0;}return $str;} //prepare variable for insertation into database
    static public function stringStartswith(string $haystack, string $needle):bool  {return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;}
    static public function extractFirstInt($str):int                { $str = (string)$str; preg_match('/\d+/', $str, $matches); if(isset($matches[0])){ return self::posInt($matches[0]);  } else {return 0;}}
    static public function posInt($num):int		                    { $num = (string)$num; $num = ltrim($num,'0'); if(!self::isInt($num)){return 0;} $num = (int)$num; if($num<=0){return 0;}return $num;}
    static public function isInt($num):bool		                    { $num = (string)$num; $num=  self::trim($num); if($num === "0"){return true;} $num = ltrim($num,'0'); if(filter_var($num,FILTER_VALIDATE_INT)){return true;}else{return false;}}
 


    //############################################################################
    //message & data handling
    static public function data(array $array):string                    {if(!is_array($array)) {self::error("an array must be used as function argument");} if(!isset($array['success']))   {$array['success']=true;} if(!isset($array['serverStatus'])){$array['serverStatus']="Request Completed";} if(self::$arrayParams['output'] === "json"){$array = self::toJson($array);}elseif(self::$arrayParams['output'] === "xml"){$array = self::toXml($array);} else{$array = print_r($array,true);}return $array;}
    static public function error(string $msg, bool $doReturn=false)     {$array=[];$array['success']=false;$array['serverStatus']=$msg;$array = self::data($array);if($doReturn){return $array;} else{ if(self::$reponseCode==0) { self::setReponseCode(400); } echo $array;exit;} } 
    static public function success(string $msg, bool $doReturn=false)   {$array=[];$array = self::data($array);if($doReturn){return $array;} else{echo $array;exit;} }  
    

    //############################################################################
    //private helper methods
    static private function returnRootWeb():string			        { $doc_root=str_ireplace("\\","/",$_SERVER['DOCUMENT_ROOT']);$doc_file=str_ireplace("\\","/",__FILE__);$rootdir=str_ireplace($doc_root,"",$doc_file);$rootdir=dirname(dirname(dirname($rootdir)));$rootdir=trim($rootdir,"/");if(self::isSsl()){$schema="https";}else{$schema="http";}$rootdir=$schema."://".$_SERVER['HTTP_HOST']."/".$rootdir;return $rootdir;}
    static private function isGroupedArray($obj)                    { return false;}
    static private function castNumericValues(array &$array)        { return;}
    static private function nullifyEmptyArrays(array &$array)       { return;}
    static private function array2JsonFormat(array $array)          { $out = [];if(!is_array($array)) { $out = $array; }else{ foreach ($array as $key => $value)  {  if(!is_array($value))   { $out[$key] = $value; } elseif(sizeof($value)==0) { $out[$key] = $value; } elseif(!self::isGroupedArray($value))  { $out[$key] = self::array2JsonFormat($value); }  else {  $out[$key]=self::convertGroupedArray($value); }  }} return $out; }
 



}

?>