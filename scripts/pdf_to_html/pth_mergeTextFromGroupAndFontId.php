<?php
declare(strict_types=1);
//####################################################################
/*
    - find most common font within group
    - find odd one out, but only if it is in between the most comment one.
    - odd one out be appended in the previous row

*/
class pth_mergeTextFromGroupAndFontId
{
    
    private  $maxBlockYSeparator =  8;
    
    public function __construct(&$obj)
    {
        
      //---------------------------------------------------
      //gather groupNumbers together in new object(s)
      $objGroups = [];
      foreach ($obj['content'] as $index => $properties) 
      {
          if($properties['groupNumber'] == 0)                           { continue; }
          if(!isset($objGroups[$properties['groupNumber']])){ $objGroups[$properties['groupNumber']]=[];}
         
          $objGroups[$properties['groupNumber']][$index] = $properties;         
      }

      $objGroups = array_values($objGroups);
      //--------------------------------------------------
      //traverse the gathers groups, and get the most comon fontId
      foreach ($objGroups as $key => $nodes) 
      {
            $keys = array_keys($nodes);
            $len =  sizeof($keys);
            for($n=0;$n<$len;$n++)
            {
                $index =    $keys[$n];
                $indexPrev = $n - 1;
                $indexNext = $n + 1;
                if(!isset($keys[$indexPrev])) { continue; }
                if(!isset($keys[$indexNext])) { continue; }
                $indexPrev = $keys[$indexPrev];
                $indexNext = $keys[$indexNext];

                $fontPrev = $nodes[$indexPrev]['fontId'];
                $font =     $nodes[$index]['fontId'];
                $fontNext = $nodes[$indexNext]['fontId'];
                
                $topMaxPrev =   $nodes[$indexPrev]['top']+$nodes[$indexPrev]['height'];
                $topMax =       $nodes[$index]['top']+$nodes[$index]['height'];

                if($fontPrev <> $fontNext)  { continue; }
                if($fontPrev == $font)      { continue; }
                if( abs($nodes[$index]['top'] - $topMaxPrev) > $this->maxBlockYSeparator ) { continue; }
                if( abs($nodes[$indexNext]['top'] - $topMax) > $this->maxBlockYSeparator ) { continue; }

                digi_pdf_to_html::mergeBlocks($obj,$indexPrev,$index,false);    
                digi_pdf_to_html::mergeBlocks($obj,$indexPrev,$indexNext,false); 
                $n = $n + 3; //takes 3 steps for the indexNext-index to be beyond indexPrev position
            }

      }

        $obj['content'] = array_values ($obj['content']); //re-index all dat    
    }

}

?>