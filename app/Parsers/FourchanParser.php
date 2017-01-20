<?php

namespace App\Parsers;

class FourchanParser
{
    public function parseImage($html_content)
    {
        $image_list = array();
        
        /*
        is.4chan.org\/\S+\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)
        is((0-9)|\w*?).4chan.org\/\S+\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)
        (0-9|\w*?) : optional string after is and before . (dot)
        */
        preg_match_all("#is((0-9)|\w*?).4chan.org\/\S+\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)#", $html_content, $match);

        foreach ($match[0] as $key => $value) {
            $value = 'http://' . $value;
            if(!in_array($value, $image_list)) {
                $image_list[] = $value;
            }
        }

        return $image_list;
    }
}