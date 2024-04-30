<?php
declare(strict_types=1);
//remove last hyphen character, and this is used to continue text on a next line. Since we are merging texts this must be removed.
//this class must be done before any other text-merger, as the hypen must be detected in the orignal content, and located as the last character

/*
    for example source:

    Today I walked into a room and saw a data-
    base interface on my computer.

    target output must become:
    Today I walked into a room and saw a database interface on my computer.

*/

class pth_removeLastHyphen
{
   
    public function __construct(&$obj)
    {
      
        foreach ($obj['content'] as $index => $properties) 
        {
            if($properties['tag'] === "image")                              { continue; }
            if(sys::length($properties['content']) <= 1)                    { continue; }
            if(!sys::stringEndswith($properties['content'],"-"))            { continue; }
            $obj['content'][$index]['content'] = rtrim($obj['content'][$index]['content'], "-");
        }
    }
}

?>