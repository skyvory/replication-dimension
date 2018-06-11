<?php

namespace App\Parsers;

class ArchivedmoeParser
{
    public function parseImage($html_content)
    {
        $image_list = array();
        
        // preg_match_all("#<a href=\"https:\/\/archiveofsins.com\/h\/full_image\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)\" target#", $html_content, $match);
        preg_match_all("#<a href=\"https:\/\/archived.moe\/h\/redirect\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)\" target#", $html_content, $match);

        foreach ($match[0] as $key => $value) {
            $value = substr($value, 9);
            $value = rtrim($value, "target");
            $value = rtrim($value, " ");
            $value = rtrim($value, "\"");
            if(substr($value, -1) == "\\") {
              $value = rtrim($value, "\\");
            }
            if(!in_array($value, $image_list)) {
                $image_list[] = $value;
            }
        }

        return $image_list;
    }
}