<?php

namespace App\Parsers;
use App\Parsers\DeviantartParser;
use App\Parsers\NichanParser;
use App\Parsers\FourchanParser;
// use x-parser

trait ParserManager
{
	public function getParserBridge() {
		return [
			'deviantart.com' => 'deviantart',
			'2chan.net' => '2chan',
			'4chan.org' => '4chan',
			'7chan.org' => '7chan'
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
			case "4chan":
				$analyze = new FourchanParser();
				$image_list = $analyze->parseImage($html_content);
				break;
			case "7chan":
				$analyze = new NanachanParser();
				$image_list = $analyze->parseImage($html_content);
				break;
			case "desuarchive":
				$analyze = new DesuarchiveParser();
				$image_list = $analyze->parseImage($html_content);
				break;
			default:
				$image_list = array('no'=>'no');
		}

		return $image_list;
	}

}
 