<?php

namespace App\Http\Controllers;

use InterventionImage;

trait Writer
{
	public function writeHtmlToDisk($content, $directory) {
		// iconv(mb_detect_encoding($directory, mb_detect_order(), true), "UTF-8", $directory);
		// $directory = utf8_decode($directory);
		// $directory = urlencode($directory);
		$directory = mb_convert_encoding($directory, "SJIS");
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
		// $directory = utf8_encode($directory);
		// $directory = iconv ("UTF-8", "UTF-16", $directory); 
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

	public function materializeThumbnail($source_file_path, $thumbnail_file_path) {
		// check if thumbnail already exists
		if(file_exists($thumbnail_file_path)) {
			return;
		}
		// 
		if(!file_exists(dirname($thumbnail_file_path))) {
			$this->prepareDirectory(dirname($thumbnail_file_path));
		}
		# thumbnail creation
		$source_file_path = mb_convert_encoding($source_file_path, "SJIS");
		$img = InterventionImage::make($source_file_path);
		$img->resize(300, null, function($constraint) {
			$constraint->aspectRatio();
			$constraint->upsize();
		});
		$img->save($thumbnail_file_path, 50);
		return true;
	}
}