<?php
namespace App\Parsers;

// site: gurochan.ch
class GurochanParser
{
    public function parseImage($html_content)
    {
        $image_list = array();

        preg_match_all("#\/s\/src\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)#", $html_content, $match);

        foreach($match[0] as $key => $value) {
            $value = 'https://gurochan.ch' . $value;
            if(!in_array($value, $image_list)) {
                $image_list[] = $value;
            }
        }

        return $image_list;
    }
}