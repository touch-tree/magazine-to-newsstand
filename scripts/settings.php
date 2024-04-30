<?php
declare(strict_types=1);
//############################################################################
class settings 
{

    //--------------------------------------------------------
    //server related
	static public function server():array												
	{
		$array = [];

		//-------------------------------
		//general
		$array['storage'] = 		files::standardizePath("/storage/");
		$array['urlLogoOnLight'] = 	sys::$arrayParams['rootWeb']."/images/companyLogo.png" ; 
		$array['urlLogoOnDark'] = 	sys::$arrayParams['rootWeb']."/images/companyLogoForDark.svg" ; 
		$array['tempFolder'] = 		files::standardizePath("/tmp/"); //sys_get_temp_dir(); 
		$array['serviceBlockPath']=	files::standardizePath($array['tempFolder']."/serviceBlocker.txt");

		//-------------------------------
		//relation related
		$array['relation'] = [];
		$array['relation']['folderStorage'] = 				files::standardizePath("/relations_disk/");

		//-------------------------------
		//digital related
		$array['digital'] = [];
		$array['digital']['folderRelationCustomedHtml'] = 	files::standardizePath("/relations_customisedHtml/");
		$array['digital']['maxGeoMetres']		= 			100;
		$array['digital']['folderPrivateDocuments'] = 		files::standardizePath("/relations_pdf/");
		$array['digital']['folderArticleHtmlPages'] = 		files::standardizePath("/articles_htmlpages/");
		$array['digital']['folderArticleAudioFiles'] = 		files::standardizePath("/articles_htmlpages/");
		$array['digital']['folderBanners'] = 				files::standardizePath("/adverts/");
		$array['digital']['folderHighlights'] = 			files::standardizePath("/adverts/");
		$array['digital']['folderSettings']=				files::standardizePath("/general_settings/");
		$array['digital']['customJsCss']=					files::standardizePath($array['digital']['folderSettings']."/digi_javascript.txt");
		$array['digital']['folderArticleImages'] = 			files::standardizePath("/articles_pdf/");
		$array['digital']['databaseMapping']=				files::standardizePath($array['storage']."/databaseDigiMapping.json");
		$array['digital']['urlPlatform']= 					files::standardizePath(sys::$arrayParams['rootWeb']."/web/digi/platform.php"); 
		$array['digital']['urlAccess']= 					files::standardizePath(sys::$arrayParams['rootWeb']."/web/digi/index.php") ; 
		$array['digital']['privacyPolicy']=					files::standardizePath($array['digital']['folderSettings']."/digi_privacy.html");

		$array['digital']['articles'] = [];
		$array['digital']['articles']['widthThumb']=		160;
		$array['digital']['articles']['widthMedium']=		600;
		$array['digital']['articles']['nameBase']=			"page";
		$array['digital']['articles']['nameThumb']=			"page_thumb";
		$array['digital']['articles']['nameMedium']=		"page_medium";

		$array['digital']['articles']['branch'] = [];
		$array['digital']['articles']['branch']['allowOverrideSettingNewspaper'] = 	false;
		$array['digital']['articles']['branch']['allowOverrideSettingRssTitle'] = 	false;
		$array['digital']['articles']['branch']['allowOverrideSettingRssUrl'] = 	false;
		
		//-------------------------------
		//public-access folders
		$array['public'] = [];
		$array['public']['folderLogFiles'] = 				files::standardizePath("/logfiles/");
		$array['public']['folderArticleImages'] = 			files::standardizePath("/articles/");
		$array['public']['folderArticleCategories'] = 		files::standardizePath("/articleCategories/");
		
		return $array;
	}

    //############################################################################################
	//############################################################################################
	//############################################################################################
    //department related ($departmentId=0 equals the defaults)
	static public function department(int $departmentId=0):array												
	{
		$array = [];

		//-------------------------------
		//article related
		$array['article'] = [];
		$array['article']['branch'] = [];				
		$array['article']['branch']['allowOverrideGroup'] = 				false;
		$array['article']['branch']['allowOverrideCategory'] = 				false;
		$array['article']['branch']['allowOverrideSupplier'] = 				false;
		$array['article']['branch']['allowOverrideManufacturer'] = 			false;
		$array['article']['branch']['allowOverrideMediaDescription'] = 		false;

		return $array;

	}

   	//############################################################################################
	//############################################################################################
	//############################################################################################

}

?>