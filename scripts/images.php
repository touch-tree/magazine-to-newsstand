<?php
declare(strict_types=1);
//############################################################################
class images 
{
	static public  $settings =   [];
    //############################################################################
    //html base64 image data
	static public function base64IsFile($src)			{if(stristr($src,"data:image")){return true;}else{return false;}}
	static public function base64Extention($src)		{$src = explode(';base64,',stristr($src, ';base64,', true));if(empty($src[0])){return false;}preg_match('/^data:(.*)\/(.*)/', $src[0], $match); $ext = $match[2];return $ext;} /* returns false onno extension */
	static public function base64ImageData($src)		{return base64_decode(preg_replace('#^data:image/\w+;base64,#i','',$src));}		
	static public function base64FromBlob($blob,$ext)	{return "data:image/".$ext.";base64,".base64_encode($blob);}


	//############################################################################
	//image settings
	static private function resetProperties()				{self::$settings['dst_x']=0; self::$settings['dst_y']=0; self::$settings['src_x']=0; self::$settings['src_y']=0; self::$settings['isInterpolation']=true;self::$settings['targetWidth']=0;self::$settings['targetHeight']=0;self::$settings['imageWidth']=0;self::$settings['imageHeight']=0;self::$settings['extension']=null;}
	static public function detectImageDimensions($path)		{if(!is_file($path)) { sys::error("Image-path '".$path."' does not exist");} self::$settings['extension'] = strtolower(files::fileExtension($path)); if(in_array(self::$settings['extension'],array('jpeg','jpe'))){self::$settings['extension']="jpg";} list($pic_width, $pic_height, $type, $attr) = getimagesize($path);self::$settings['imageWidth']=	sys::posInt($pic_width);self::$settings['imageHeight']=	sys::posInt($pic_height);}

	//############################################################################
	//actions
	static public function resizeImage($path,$w=0,$h=0)
	{
		self::resetProperties();
		self::$settings['targetWidth']=		$w;
		self::$settings['targetHeight']=	$h;
		self::processImage($path);	
	}

	static private function processImage($path)	
	{
		if(!is_file($path)) { sys::error("Image-path '".$path."' does not exist");}
		self::detectImageDimensions($path);
        if(self::$settings['targetWidth'] == 0 and self::$settings['targetHeight'] == 0)	{sys::error("Please specify target width and/or height for image resizing");}
        if(self::$settings['imageWidth'] == 0 or self::$settings['imageHeight']==0)			{sys::error("The image does not appear to have dimensions");}
		//-------------------------------------------------------
        if(self::$settings['targetHeight'] == 0 )
        {
            $resize_perc=            			round((self::$settings['targetWidth']/self::$settings['imageWidth']),2)*100;
            self::$settings['targetHeight']=    round(($resize_perc * self::$settings['imageHeight']/100));
        }
        if(self::$settings['targetWidth'] == 0 )
        {
            $resize_perc=            			round((self::$settings['targetHeight'] / self::$settings['imageHeight']),2)*100;
            self::$settings['targetWidth']=     round(($resize_perc * self::$settings['imageWidth']/100));
        }
        //-------------------------------------------------------
		self::$settings['targetWidth'] = 	sys::posInt(self::$settings['targetWidth']);
		self::$settings['targetHeight'] = 	sys::posInt(self::$settings['targetHeight']);

        $img_id=null;
        $img_temp	=								imagecreatetruecolor(self::$settings['targetWidth'],self::$settings['targetHeight']);
        if(self::$settings['extension']==="jpg")	{$img_id = @imagecreatefromjpeg($path);}
        if(self::$settings['extension']==="gif")	{$img_id = @ImageCreatefromgif($path);}
        if(self::$settings['extension']==="png")	{$img_id = @ImageCreatefrompng($path);}  

        
        if(!$img_id){sys::error("The image created is not a valid resource or is corrupted");}
		
		//keep transpacency
        if(self::$settings['extension'] === "gif" or self::$settings['extension'] === "png")
		{
		    imagealphablending($img_temp, false);
		    imagesavealpha($img_temp, true);
		    imagecolortransparent($img_temp, imagecolorallocatealpha($img_temp,255, 255, 255, 127));
  		}
        
        if(self::$settings['isInterpolation'])
        {
        	imagecopyresampled($img_temp,$img_id,self::$settings['dst_x'],self::$settings['dst_y'],self::$settings['src_x'],self::$settings['src_y'],self::$settings['targetWidth'],self::$settings['targetHeight'],self::$settings['imageWidth'],self::$settings['imageHeight']);
 		}
 		else
 		{
        	imagecopyresized($img_temp,$img_id,self::$settings['dst_x'],self::$settings['dst_y'],self::$settings['src_x'],self::$settings['src_y'],self::$settings['targetWidth'],self::$settings['targetHeight'],self::$settings['imageWidth'],self::$settings['imageHeight']);	
		}

  		//copy to target path: $image_path="c:/temp/copy.jpeg"; //beter to just override existing one
        if(self::$settings['extension'] === "jpg"){imagejpeg($img_temp,$path);}
        if(self::$settings['extension'] === "gif"){imagegif($img_temp,$path);}
        if(self::$settings['extension'] === "png"){imagepng($img_temp,$path);}
        imagedestroy($img_temp); //Free up memory  
        
	}

	

 

}

?>