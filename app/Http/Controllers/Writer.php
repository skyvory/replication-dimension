<?php

namespace App\Http\Controllers;

use InterventionImage;

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
		$img = InterventionImage::make($source_file_path);
		$img->resize(300, null, function($constraint) {
			$constraint->aspectRatio();
			$constraint->upsize();
		});
		$img->save($thumbnail_file_path, 50);
		return true;
	}
}