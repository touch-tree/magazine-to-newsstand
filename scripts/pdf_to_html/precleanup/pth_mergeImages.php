<?php
declare(strict_types=1);
//#####################################################################


class pth_mergeImages
{    

    private  $margin= 1;

    public function __construct()
    {
        $obj = &digi_pdf_to_html::$arrayPages[digi_pdf_to_html::$pageNumber]; 
        digi_pdf_to_html::sortByTopThenLeftAsc();
        //-------------------------------
        $this->cleanup($obj);       
    }
    
    //#####################################################################
    private function cleanup(&$obj)
    {
        $this->sameLeftAligned($obj);
        
  
    }

    //#####################################################################
    private function sameLeftAligned(&$obj)
    {
        $imageNodes =               digi_pdf_to_html::returnProperties("tag","image");
        $arrayLeftCollection =      digi_pdf_to_html::collectPropertyValues($imageNodes,"left",0);//exact left position, so use 0

        //-------------------------------------
        $imageCollection = [];
        foreach( $arrayLeftCollection as $left => $indexes) 
        {
            $len = sizeof($indexes);
            if($len<=1){ continue; }
            if(!isset($imageCollection[$left])) { $imageCollection[$left]=[];}
            for($n=0;$n<$len;$n++)
            {
                if(!isset($indexes[$n+1]))          { break; }
                $index =        $indexes[$n];
                $index2=        $indexes[$n+1]; 
                $boundary =     digi_pdf_to_html::returnBoundary([$index]);
                $boundary2 =    digi_pdf_to_html::returnBoundary([$index2]);
                $diffY =        abs($boundary['maxTop']-$boundary2['top']);
                if($diffY > $this->margin ) { continue; }

                $imageCollection[$left][] = $index;
                $imageCollection[$left][] = $index2;

            }

            $imageCollection[$left] = array_values(array_unique($imageCollection[$left]));
            if(sizeof($imageCollection[$left]) <=1 ) {unset($imageCollection[$left]);}
        }
        
        //-------------------------------------
        foreach( $imageCollection as $left => $indexes) 
        {
            $boundary = digi_pdf_to_html::returnBoundary($indexes);
            $nodes =    digi_pdf_to_html::returnNodesFromIndexes($indexes);
            $arrayImages=[];
            $arrayImages['fileName']=   [];
            $arrayImages['ext']=        [];
            $arrayImages['path']=       [];
            $arrayImages['width']=      [];
            $arrayImages['height']=     [];

            //Initialize canvas dimensions
            $maxWidth =     0;
            $maxHeight =    0;  
            $extension=     null;

            foreach( $nodes as $index => $properties) 
            {
                //read image data
                $img = digi_pdf_to_html::$processFolder."/".$properties['content'];
                images::detectImageDimensions($img);
                if(!isset(images::$settings['imageWidth']) or sys::posInt(images::$settings['imageWidth']) == 0 )     { continue 2; }
                if(!isset(images::$settings['imageHeight']) or sys::posInt(images::$settings['imageHeight']) == 0 )   { continue 2; }
                $ext = files::fileExtension($img);
                if( sizeof($arrayImages['ext']) > 0 && !in_array($ext,$arrayImages['ext']))                           { continue 2; }
                
                $arrayImages['fileName'][]=     $properties['content'];
                $arrayImages['path'][]=         $img;
                $arrayImages['width'][]=        images::$settings['imageWidth'];
                $arrayImages['height'][]=       images::$settings['imageHeight'];
                $arrayImages['ext'][]=          strtolower($ext);

                $maxWidth =  max( [$maxWidth,images::$settings['imageWidth']]);
                $maxHeight = $maxHeight + images::$settings['imageHeight'];
            }

            $mergedImage = imagecreatetruecolor($maxWidth, $maxHeight);
            imagealphablending($mergedImage, false);
            imagesavealpha($mergedImage, true);

            // Initialize x-coordinate for positioning
            $y = 0;         
            $len = sizeof($arrayImages['path']);
            for($n=0;$n<$len;$n++)
            {
                $image = images::returnImage($arrayImages['path'][$n]);
                $width = $arrayImages['width'][$n];
                $height= $arrayImages['height'][$n];
                imagecopy($mergedImage, $image, 0, $y, 0, 0, $width, $height);
                $y += $height; 
                imagedestroy($image); 
            }

            $outputPath = digi_pdf_to_html::$processFolder. "/merged_".digi_pdf_to_html::$pageNumber."_".$boundary['top']."_".$boundary['left'].".jpg";
            images::writeImage($mergedImage, $outputPath);

            //merge $indexes to the first value of $indexes
            $firstIndex = $indexes[0];

            array_shift($indexes);
            $teller=0;
            while(sizeof($indexes)>0)
            {
                digi_pdf_to_html::mergeNodes($firstIndex, $indexes[0],false);  
                array_shift($indexes);  
            }

            //re-index
            $obj['nodes'][$firstIndex]['content'] = basename($outputPath);
            digi_pdf_to_html::reIndex();
            $this->cleanup($obj);
            return;     
        }

      
    }
    //##################################################################


}

?>