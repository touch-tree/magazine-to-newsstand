<?php
declare(strict_types=1);
//############################################################################
class installation 
{
	static private $array_ext= 		null;


	static private function returnStatus(array $array):array					{if(!isset($array['success'])){$array['success']=true;}if(!isset($array['serverStatus'])){$array['serverStatus']="OK";}return $array;}
	static private function returnError($msg)									{$data = [];$data['serverStatus'] = $msg;$data['success'] = 		false; return self::returnStatus($data);}
	static private function returnSuccess($msg="OK")							{$data = [];$data['serverStatus'] = $msg;$data['success'] = 		true; return self::returnStatus($data);}
	static private function setExt()											{if(isset(self::$array_ext)){return;}self::$array_ext = get_loaded_extensions();self::$array_ext = array_map('strtolower', self::$array_ext);}
	//--------------------------------------------------------
    //server related
	static public function execStatus():array									{$data = [];$success = false;if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {try {$response = @exec('echo EXEC');if(isset($response)){$response = sys::trim($response);if($response  === 'EXEC') { $success = true;}}  } catch (Exception $e) {}}if(!$success){$data['serverStatus'] = "No access to exec()"; $data['success'] = false; }return self::returnStatus($data);}
	static public function shellExecStatus():array								{$data = []; $success = false;if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {try {$response = @shell_exec('echo EXEC');if(isset($response)){$response = sys::trim($response);if($response  === 'EXEC') { $success = true;}}  } catch (Exception $e) {}}if(!$success){$data['serverStatus']  = "No access to shell_exec()"; $data['success'] = false;}return self::returnStatus($data);}
	static public function imagickStatus():array								{$data = [];if(!extension_loaded('imagick')){$data['serverStatus']  = "Imagick is not installed"; $data['success'] = false;}return self::returnStatus($data);}
	static public function domDocumentStatus():array							{$data = [];if(!class_exists("DomDocument")){$data['serverStatus']  = "DomDocument is not installed"; $data['success'] = false;}return self::returnStatus($data);}
	static public function libxmlStatus():array									{$data = []; self::setExt(); if(!in_array("libxml",self::$array_ext)){$data['serverStatus']  = "libxml is not installed"; $data['success'] = false;}return self::returnStatus($data);}
	static public function openSslStatus():array								{$data = []; self::setExt(); if(!in_array("openssl",self::$array_ext)){$data['serverStatus']  = "openssl is not installed"; $data['success'] = false;}return self::returnStatus($data);}
	static public function gdStatus():array										{$data = []; self::setExt(); if(!in_array("gd",self::$array_ext)){$data['serverStatus']  = "openssl is not installed"; $data['success'] = false;}return self::returnStatus($data);}
	static public function mbStringStatus():array								{$data = []; self::setExt(); if(!in_array("mbstring",self::$array_ext)){$data['serverStatus']  = "mbstring is not installed"; $data['success'] = false;}return self::returnStatus($data);}
	static public function sessionDurationStatus():array						{$data = [];if(!ini_get('session.gc_maxlifetime')>=57600){$data['serverStatus']  = "gc_maxlifetime is to short";$data['success'] = false;}return self::returnStatus($data);}
	static public function executionTimeoutStatus():array						{$data = [];$max_time = ini_get("max_execution_time"); if($max_time<3600){$data['serverStatus']  = "max_execution_time is to short";$data['success'] = false;} return self::returnStatus($data);}
	static public function tempDirectoryStatus():array							{$data = [];$tempFolder = settings::server()['tempFolder'];if(!is_writable($tempFolder)){$data['serverStatus']  = "TempFolder is not writable"; $data['success'] = false;}return self::returnStatus($data);}
	static public function storageDirectoryStatus():array						{$data = [];$tempFolder = settings::server()['storage'];if(!is_writable($tempFolder)){$data['serverStatus']  = "Storage folder is not writable";$data['success'] = false;}return self::returnStatus($data);}
	static public function ftpStatus():array									{$data = [];if(!function_exists('ftp_connect')) {$data['serverStatus']  = "ftp_connect not enabled";$data['success'] = false;}return self::returnStatus($data);}
	static public function procOpenStatus():array								{$data = [];$success = false;if (function_exists('proc_open') && !in_array('proc_open', explode(',', ini_get('disable_functions')))) {try {$descriptorspec = array(0 => array("pipe", "r"),1 => array("pipe", "w"),2 => array("pipe", "w"));$process = proc_open('echo EXEC', $descriptorspec, $pipes);if (is_resource($process)) {fclose($pipes[0]);$response = stream_get_contents($pipes[1]);fclose($pipes[1]);fclose($pipes[2]);proc_close($process);if (isset($response)) {$response = sys::trim($response);if ($response === 'EXEC') {$success = true;}}}} catch (Exception $e) {}}if (!$success) {$data['serverStatus'] = "No access to proc_open()";$data['success'] = false;}return self::returnStatus($data);}
	static public function ghostScriptStatus():array							{$out = self::procOpenStatus();if(!$out['success'])  { return self::returnError("No access to proc_open(), thus no Ghostscript validation possible");}if(sys::$arrayParams['OS'] === "WINDOWS"){$out = shell::command('reg query "HKEY_LOCAL_MACHINE\\SOFTWARE\\GPL Ghostscript"');if($out['error']) { return self::returnError("Ghostscript not found on server");}}else{$out = shell::command('gs --version');	if($out['error']) { return self::returnError("Ghostscript not found on server");}} return self::returnSuccess();}
	static public function zlibStatus():array									{if (extension_loaded('zlib')) {return self::returnSuccess();} else {return self::returnError("zlib is not installed")	;}}
	static public function gzcompressStatus():array								{if(function_exists('gzcompress')) {return self::returnSuccess();} else {return self::returnError("gzcompress is not installed")	;}}
	static public function diskSpaceStatus():array								{$bytes = disk_free_space(".");$gb = round($bytes/1024/1024/1024,2);if($gb < 20){return self::returnError("Insufficient disk space (".$gb."GB) ");}else {return self::returnSuccess($gb."GB ");}}
	static public function zipArchiveStatus():array								{if (class_exists('ZipArchive')) {return self::returnSuccess();} else {return self::returnError("ZipArchive is not installed")	;}}
	static public function gzopenStatus():array									{if (function_exists('gzopen')) {return self::returnSuccess();} else {return self::returnError("ZipArchive is not installed")	;}}
	static public function popplerStatus():array								{$out = self::procOpenStatus();$folder = null; $baseCommand = "pdftohtml"; if(!$out['success']) { return self::returnError("No access to proc_open(), thus no Poppler validation possible"); } if(sys::$arrayParams['OS'] === "WINDOWS"){$command = 'where /R "C:\Program Files" pdftohtml.exe'; $out = shell::command($command); if($out['error']){ return self::returnError("Could not find poppler files in your Windows Progam Files (sub) folders"); } $line = explode("\n",$out['response'])[0];if(!stristr($line,"pdftohtml")) { return self::returnError("Could not find poppler file pdftohtml in your Windows Progam Files (sub) folders"); }  $folder = dirname(files::standardizePath($line))."/";}if(isset($folder)) {$baseCommand = $folder.$baseCommand;  if(stristr($baseCommand," ")) { $baseCommand = '"'.$baseCommand.'"'; } } $command = $baseCommand." -v";$out = shell::command($command); if($out['error']) {if(!stristr($out['error'],"version")){return self::returnError("Poppler (pdftohtml) not found on server");} /* puts version data in the error output */ } $data = self::returnSuccess();$data['applicationFolder']=$folder; $data['baseCommand']=$baseCommand; return $data;}	

}



	


?>