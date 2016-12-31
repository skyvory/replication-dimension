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
		# hotfix for unsupported filetype
		if(pathinfo($source_file_path, PATHINFO_EXTENSION) == '.webm') {
			return false;
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

	public function parseSyntacticDirectory($directory, $options) {
		if(!empty($options['title'])) {
			$title = $options['title'];
		}
		if(!empty($options['url'])) {
			$url = $options['url'];
		}

		if(!empty($directory)) {
			preg_match_all("#\{([^\}]*)\}#", $directory, $matches);
			$i = 0;
			foreach($matches[1] as $value) {
				switch ($value) {
					case '2ch:id':
						$id = substr($url, strrpos($url, '/') + 1);
						$id = pathinfo($id, PATHINFO_FILENAME);
						$directory = str_replace($matches[0][$i], $id, $directory);
						break;
					case '2ch:title':
						$directory = str_replace($matches[0][$i], $title, $directory);
						break;
					case '4ch:id':
						// $id = substr($url, strpos($url, '/') + 1);
						$id = explode('/', $url);
						$id = end($id);
						$directory = str_replace($matches[0][$i], $id, $directory);
						break;
					case '4ch:title':
						$title = explode('-', $title);
						$title = trim($title[1]) . '-' . trim($title[3]);
						$directory = str_replace($matches[0][$i], $title, $directory);
						break;
					default:
						break;
				}
				$i++;
			}
			$restricted_windows_filename_chars = array_merge(array_map('chr', range(0,31)), array("<", ">", ":", '"', "/", "|", "?", "*"));
			$dir_pre = substr($directory, 0, 2);
			$dir_post = substr($directory, 2);
			$dir_post = str_replace($restricted_windows_filename_chars, "", $dir_post);
			$directory = $dir_pre . $dir_post;
			return $directory;
		}
	}
}