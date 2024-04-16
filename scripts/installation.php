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
	static public function procOpenStatus():array								{$data = [];$success = true; return self::returnStatus($data);}



}



	


?>