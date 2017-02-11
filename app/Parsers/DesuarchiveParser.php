<?php
namespace App\Parsers;

class DesuarchiveParser
{
    public function parseImage($html_content)
    {
        $image_list = array();

        preg_match_all("#https?:\/\/desu-usergeneratedcontent.xyz\/\S+\/image\/\S+\.(jpg|png|gif|webp|jpeg|bmp|webm)#", $html_content, $match);

        foreach($match[0] as $key => $value) {
            if(!in_array($value, $image_list)) {
                $image_list[] = $value;
            }
        }

        return $image_list;
    }
}