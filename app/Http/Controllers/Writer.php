<?php

namespace App\Http\Controllers;

trait Writer
{
	public function writeHtmlToDisk($content, $directory) {
		$date = date('YmdHis');
		$this->prepareDirectory($directory);
		$directory = $directory . '/' . $date . '.html';
		// try {
			$file = fopen($directory, 'w');
			fwrite($file, $content);
			fclose($file);
		// } catch (\Exception $e) {
			// throw new \Symfony\Component\HttpKernel\Exception\HttpException('Write HTML failed');
		// }

		return true;
	}
	public function prepareDirectory($directory) {
		try {
			if(!is_dir($directory)) {
				mkdir($directory, 0777, true);
			}
		}
		catch(\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Directory preparation failed!');
		}

		return true;
	}
}