<?php
declare(strict_types=1);
//############################################################################
class shell 
{
	
	static private  $isInitiated =   false;
	static private  $checkedStatus = false;

	//----------------------------------------------
	static public function init(){
		self::$checkedStatus =  true;
		self::$isInitiated = 	installation::procOpenStatus()['success'];
		if(!self::$isInitiated) {return;}
	}

	//----------------------------------------------
	//str can be string or array
	static public function command($str,$cwd=null, $env=null)
	{
			if(!self::$checkedStatus) 	{self::init();}
			if(!self::$isInitiated) 	{sys::error("proc_open not initiated");}
			
			$arrayResponse =  				[];
			$arrayResponse['response'] = 	null;
			$arrayResponse['error'] = 		null;
			$arrayResponse['exitCode'] = 	null;

			//--------------
			//allocate log files (use log-files because $pipes[1] may hang when console has output suppressed)
			//$stdout = "D:/temp/images/stdout.txt";
			//$stderr = "D:/temp/images/stderr.txt";

			$path_stdout = files::standardizePath(settings::server()['tempFolder']."/stdout_".connection::$databaseName."_".sys::compressString(dates::dateTime()).".txt");
			$path_stderr = files::standardizePath(settings::server()['tempFolder']."/stderr_".connection::$databaseName."_".sys::compressString(dates::dateTime()).".txt");
			
			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
				1 => array("file", $path_stdout, "w"),  // stdout is a file to write to
				2 => array("file", $path_stderr, "w") 	// stderr is a file to write to
			 );

			 $process = @proc_open($str, $descriptorspec, $pipes, $cwd, $env);
 
			 if (is_resource($process)) 
			 {
					fclose($pipes[0]);
					$arrayResponse['exitCode'] = proc_close($process);
					$process = null;

					if(files::isFile($path_stdout)) 
					{
						$content = files::fileGetContents($path_stdout);
						if(sys::length($content)>0) { $arrayResponse['response']=$content;}
						files::deleteFile($path_stdout);
					}

					if(files::isFile($path_stderr)) 
					{
						$content = files::fileGetContents($path_stderr);
						if(sys::length($content)>0) 
						{
							//remove duplicate warning/errors
							$content = str_replace("\r","",$content); 
							if(stristr($content,"\n"))
							{
								$out = [];
								$lines = explode("\n",$content);
								$len = sizeof($lines);
								for( $n=0; $n<$len; $n++ ){if(!in_array($lines[$n],$out)) {$out[]=$lines[$n];}}
								$content = implode("\n\r",$out);
							}
					
							$arrayResponse['error']=$content;
						}
						files::deleteFile($path_stderr);
					}
				 
			 }

			 return $arrayResponse;
			
	}
	//----------------------------------------------
	/*
		example:
		 $params = array(
		"dMaxBitmap"=>          [100000000,"="],
		"o"=>                   ["page%01d.jpeg"," "],
		"dUseTrimBox" =>        [null,null]
		);

	*/
	static public function extractParams(array $array):string 
	{
	   $command = "";

	   foreach ($array as $key => $value){
		   $val =      $value[0];
		   $delim =    $value[1];
		   $command .= " -" . $key;
		   if(!isset($val)){ continue; }
		   $command.=$delim.$val;
   
	   }
	   return $command;
   }
	//----------------------------------------------




}
//############################################################################


	


?>