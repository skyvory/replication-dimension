<?php

namespace App\Parsers;
use App\Parsers\DeviantartParser;
use App\Parsers\NichanParser;
// use x-parser
// use x-parser

trait ParserManager
{
	public function getParserBridge() {
		return [
			'deviantart.com' => 'deviantart',
			'2chan.net' => '2chan',
		];
	}

	public function parseThreadContent($site_name, $html_content) {
		$image_list = array();

		switch (strtolower($site_name)) {
			case "deviantart":
				$analyze = new DeviantartParser();
				$image_list = $analyze->parseImage($html_content);
				break;
			case "2chan":
				$analyze = new NichanParser();
				$image_list = $analyze->parseImage($html_content);
				break;
			default:
				$image_list = array('no'=>'no');
		}

		return $image_list;
	}

}
 