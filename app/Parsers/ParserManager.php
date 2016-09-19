<?php

namespace App\Parsers;
use App\Parsers\DeviantartParser;
// use x-parser
// use x-parser
// use x-parser

trait ParserManager
{
	public function getParserBridge() {
		return [
			'deviantart.com' => 'deviantart',
		];
	}

	public function parseThreadContent($site_name, $html_content) {
		$image_list = array();

		switch (strtolower($site_name)) {
			case "deviantart":
				$analyze = new DeviantartParser();
				$image_list = $analyze->parseImage($html_content);
				break;
			default:
				$image_list = array('no'=>'no');
		}

		return $image_list;
	}

}
 