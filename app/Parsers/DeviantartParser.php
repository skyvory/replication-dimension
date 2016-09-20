<?php

namespace App\Parsers;

class DeviantartParser
{
	public function parseImage($html_content)
	{
		$unused_direct_pattern = '[^-]src="https?:\/\/[^/\s]+\/\S+\.(jpg|png)';
		/*
		http 	match http
		s?	optional https support
		:	literal
		\/\/	literal for //
		[^a.]	avoid subdomain a.
		[^((?!emoticons).)*$]	exclude this word (uncrtain)
		[^/\s]+	exlude / or white space with 1 or more character
		\/	literal
		\S+	match characters that's not whitespace
		\.	literal .
		(jpg|png|gif)	extensions choice 
		*/
		$pattern = 'https?:\/\/[^a.][^((?!emoticons).)*$][^/\s]+\/\S+\.(jpg|png|gif)';

		$image_list = array();

		$dom = new \DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML($html_content);
		foreach($dom->getElementsByTagName('img') as $node) {
			// echo $node->getAttribute('src');
			if(preg_match("#https?:\/\/[^a.][^((?!emoticons).)*$][^/\s]+\/\S+\.(jpg|png|gif)#", $node->getAttribute('src'))) {
				$image_list[] = $node->getAttribute('src');
			}
		}

		return $image_list;
	}
}