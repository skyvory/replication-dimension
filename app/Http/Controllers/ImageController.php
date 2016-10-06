<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Image;
use App\Thread;
use App\Transformers\StateTransformer;
use Dingo\Api\Routing\Helpers;
use InterventionImage;
use App\Http\Controllers\Writer;

class ImageController extends Controller
{
	use Writer;

	public function __construct() {}

	public function load(Request $request)
	{
		$thread_id = $request->thread_id;
		// $url = 'http:\/\/t13.deviantart.net\/WiuBWCWrAe7FpG0hkSRa0FbWLcQ=\/fit-in\/150x150\/filters:no_upscale():origin()\/pre11\/3762\/th\/pre\/i\/2012\/302\/7\/b\/droid__by_pinkeyefloyd-d5jbay5.jpg';
		$url = stripcslashes($request->url);
		$url = stripcslashes($url);

		$thread = Thread::find($thread_id);

		# prepare directory
		$this->prepareDirectory($thread->download_directory);

		# get image size
		$headers = get_headers($url);
		$content_length = -1;
		foreach($headers as $head) {
			preg_match('/Content-Length: (\d+)/', $head, $match);
			if(isset($match[1])) {
				$content_length = (int)$match[1];
				break;
			}
		}

		$source_image_size = $content_length;
		$image_name = basename($url);
		$file_path = $thread->download_directory . "\\" . $image_name;

		# check if file already exist / downloaded
		if(file_exists($file_path)) {
			if($source_image_size == filesize($file_path)) {
				$image = Image::where('thread_id', $thread_id)->where('url', $url)->where('download_status', 1)->first();
				if($image == null || $image->count() != 1) {
					$image = new Image();
					$image->thread_id = $thread_id;
					$image->name = $image_name;
					$image->url = $url;
					$image->size = $source_image_size;
					$image->download_status = 1;
					$image->save();
				}
				return response()->json([
					'data' => [
						'id' => $image->id,
						'name' => $image_name,
						'size' => $source_image_size,
						'thumb' => 'thumbnails/' . $thread_id . '/~thumb_' . $image_name,
					],
					'meta' => [
						'message' => 'Exact image already exist in assigned directory!',
					],
				]);
			}
			# case where the source image has been modified somehow (size, resolution, or entirely different image with same name and url)
			else {
				# rename old file (before redownload the new one)
				$exif = exif_read_data($file_path);
				$existing_image_date = date('YmdHis', $exif['FileDateTime']);
				$path_parts = pathinfo($file_path);
				$new_file_path = $path_parts['dirname'] . '\\' . $path_parts['filename'] . '_' . $existing_image_date . '.' . $path_parts['extension'];
				rename($file_path, $new_file_path);

				$image = Image::where('thread_id', $thread_id)->where('url', $url)->where('download_status', 1)->first();
				$image->download_status = 3;
				$image->save();
			}
		}

		$save_success = false;
		for ($iteration=0; $iteration < 3; $iteration++) { 
			$written_byte = $this->saveImage($url, $file_path);
			if($source_image_size == filesize($file_path) && $source_image_size == $written_byte) {
				$save_success = true;
				break;
			}
		}

		if(!$save_success) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Fail to save image after ' . $iteration . ' attempts.');
		}

		try {
			$image = new Image();
			$image->thread_id = $thread_id;
			$image->url = $url;
			$image->size = $source_image_size;
			$image->name = $image_name;
			$image->download_status = 1;
			$image->save();
		} catch (\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException('Update failed!');
		}

		# Prepare folder for thumbnail. Separation to prevent possible filename conflict 
		if(!is_dir('thumbnails')) {
			mkdir('thumbnails', 777);
		}
		if(!is_dir('thumbnails/' . $thread_id)) {
			mkdir('thumbnails/' . $thread_id);
		}
		# thumbnail creation
		$img = InterventionImage::make($file_path);
		$img->resize(300, null, function($constraint) {
			$constraint->aspectRatio();
			$constraint->upsize();
		});
		$img->save('thumbnails/' . $thread_id . '/~thumb_' . $image_name, 50);

		return response()->json([
			'data' => [
				'id' => $image->id,
				'name' => $image_name,
				'size' => $source_image_size,
				'thumb' => 'thumbnails/' . $thread_id . '/~thumb_' . $image_name,
			]
		]);
	}

	public function exclude($id) {
		$image = Image::find($id);
		$thread = Thread::find($image->thread_id);
		$file_path = $thread->download_directory . '\\' . $image->name;

		if(!is_dir('exclusions')) {
			mkdir('exclusions', 777);
		}

		if(file_exists($file_path)) {
			$ren = rename($file_path, 'exclusions/' . $image->name);
			if($ren) {
				$image->download_status = 2;
				$image->save();
			}
		}
		return response()->json(['meta' => ['message' => 'Exclude and block success!']]);
	}

	public function block($id) {
		$image = Image::find($id);
		$thread = Thread::find($image->thread_id);
		$file_path = $thread->download_directory . '\\' . $image->name;
		if(file_exists($file_path)) {
			if(unlink($file_path)) {
				$image->download_status = 2;
				$image->save();
			}
		}
		return response()->json(['meta' => ['message' => 'Delete and block success!']]);
	}

	protected function saveImage($url, $file_path)
	{
		try{
			$agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';
			$ch = curl_init();
			if(false == $ch) {
				throw new \Exception('failed to initialize');
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$raw = curl_exec($ch);
			if($raw == false) {
				throw new \Exception(curl_error($ch), curl_errno($ch));
			}
			curl_close ($ch);

			$fp = fopen($file_path, 'x');
			$written_byte = fwrite($fp, $raw);
			fclose($fp);
		}
		catch(\Exception $e) {
			trigger_error(sprintf(
				'curl failed with error #%d: %s',
				$e->getCode(), $e->getMessage()),
			E_USER_ERROR);
		}

		return $written_byte;
	}

	private function deleteDir($dirPath) {
		if (! is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::deleteDir($file);
			}
			else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}
}
