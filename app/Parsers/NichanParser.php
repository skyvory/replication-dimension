<?php

namespace App\Parsers;

class NichanParser
{
	public function parseImage($html_content)
	{
		/*
		http 	match http
		s?	optional https support
		:	literal
		\/\/	literal for //
		\S+	match characters that's not whitespace
		\.	literal
		2chan.net 	literal
		\/	literal
		\S+	match characters that's not whitespace
		\/	literal
		src 	literal
		\/	literal
		\S+	match characters that's not whitespace
		\.	literal .
		(jpg|png|gif)	extensions choice 
		*/
		$pattern = 'https?:\/\/\S+\.2chan.net\/\S+\/src\/\S+\.(jpg|png|gif|webp|jpeg|bmp)';

		$image_list = array();

		// $dom = new \DOMDocument;
		// libxml_use_internal_errors(true);
		// $dom->loadHTML($html_content);
		// foreach($dom->getElementsByTagName('a') as $node) {
		// 	if(preg_match("#https?:\/\/\S+\.2chan.net\/\S+\/src\/\S+\.(jpg|png|gif|webp|jpeg|bmp)#", $node->getAttribute('href'))) {
		// 		if(!in_array($node->getAttribute('href'), $image_list)) {
		// 			$image_list[] = $node->getAttribute('href');
		// 		}
		// 	}
		// }

		// regex, because ffff domdocument which cut the html content for no reason
		// preg_match_all("#https?:\/\/\S+\.2chan.net\/\S+\/src\/\S+\.(jpg|png|gif|webp|jpeg|bmp)#", $html_content, $match);
		// case with no domain on URL
		preg_match_all("#\/\S+\/src\/\S+\.(jpg|png|gif|webp|jpeg|bmp)#", $html_content, $match);

		// var_dump($match);
		foreach ($match[0] as $key => $value) {
			$value = 'https://jun.2chan.net' . $value;
			if(!in_array($value, $image_list)) {
				$image_list[] = $value;
			}
		}

		return $image_list;
	}
}