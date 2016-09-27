<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Image;
use App\Thread;
use App\Transformers\StateTransformer;
use Dingo\Api\Routing\Helpers;

class ImageController extends Controller
{
	public function __construct() {}

	public function load(Request $request)
	{
		$thread_id = $request->thread_id;
		$url = 'http:\/\/t13.deviantart.net\/WiuBWCWrAe7FpG0hkSRa0FbWLcQ=\/fit-in\/150x150\/filters:no_upscale():origin()\/pre11\/3762\/th\/pre\/i\/2012\/302\/7\/b\/droid__by_pinkeyefloyd-d5jbay5.jpg';
		// $url = stripcslashes($request->url);
		$url = stripcslashes($url);

		$thread = Thread::find($thread_id);

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

		$save_success = false;
		for ($iteration=0; $iteration <= 3; $iteration++) { 
			$written_byte = $this->saveImage($url, $file_path);
			if($source_image_size == filesize($file_path) && $source_image_size == $written_byte) {
				$save_success = true;
				break;
			}
		}

		if(!$save_success) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Fail to save image');
		}

		try {
			$image = new Image();
			$image->thread_id = $thread_id;
			$image->url = $url;
			$image->size = $source_image_size;
			$image->name = $image_name;
			$download_status = 1;
			$image->save();
		} catch (\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException('Update failed!');
		}

		>>>thumbnail creation
		return >>>json of image entity + directory to thumbnail;
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
}
