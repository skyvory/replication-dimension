<?php

namespace App\Parsers;
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

	public function parse($site_name) {
		// case if $site_name match x, call x-parse function
	}
}
 