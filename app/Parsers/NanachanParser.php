<?php
namespace App\Parsers;

class NanachanParser
{
    public function parseImage($html_content)
    {
        $image_list = array();

        preg_match_all("#https?:\/\/7chan.org\/\S+\/src\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)#", $html_content, $match);

        foreach($match[0] as $key => $value) {
            if(!in_array($value, $image_list)) {
                $image_list[] = $value;
            }
        }

        return $image_list;
    }
}