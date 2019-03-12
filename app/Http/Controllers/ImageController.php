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
use Validator;
use App\Http\Controllers\Encrypter;

class ImageController extends Controller
{
	use Writer;
	use Encrypter;

	public function __construct() {}

	public function load(Request $request)
	{
		// set_time_limit(30);
		set_time_limit(120);


		$validator = Validator::make($request->all(), [
			'thread_id' => 'required|integer',
			'url' => 'required|string',
		]);
		if($validator->fails()) {
			throw new \Dingo\Api\Exception\ResourceException('Could not create new user.', $validator->errors());
		}

		$thread_id = $request->thread_id;
		// $url = 'http:\/\/t13.deviantart.net\/WiuBWCWrAe7FpG0hkSRa0FbWLcQ=\/fit-in\/150x150\/filters:no_upscale():origin()\/pre11\/3762\/th\/pre\/i\/2012\/302\/7\/b\/droid__by_pinkeyefloyd-d5jbay5.jpg';
		$url = stripcslashes($request->url);
		$url = stripcslashes($url);

		$thread = Thread::find($thread_id);

		# prepare directory
		// $this->prepareDirectory($thread->download_directory);

		# get image size
		$headers = $this->retrieveHeaders($url);
		// var_dump($headers);
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
		// $file_path = mb_convert_encoding($file_path, 'SJIS');

		# check if file already exist / downloaded
		if(file_exists(mb_convert_encoding($file_path, 'SJIS'))) {
			if($source_image_size == filesize(mb_convert_encoding($file_path, 'SJIS'))) {
				$image = Image::where('thread_id', $thread_id)->where('url', $url)->where('download_status', 1)->first();
				if($image == null || $image->count() != 1) {
					$image = new Image();
					$image->thread_id = $thread_id;
					$image->name = $image_name;
					$image->url = $url;
					$image->size = $source_image_size;
					$image->download_status = 1;
					$image->save();

					// recreate thumbnail for the image as existing thumbnail exit on different thread folder
					$this->materializeThumbnail($file_path,  'thumbnails/' . $thread_id . '/~thumb_' . $image_name);
				}
				return response()->json([
					'data' => [
						'id' => $image->id,
						'name' => $image_name,
						'size' => $source_image_size,
						'thumb' => 'thumbnails/' . $thread_id . '/~thumb_' . $image_name . '.jpg',
					],
					'meta' => [
						'message' => 'Exact image already exist in assigned directory!',
					],
				]);
			}
			# case where the source image has been modified somehow (size, resolution, or entirely different image with same name and url)
			else {
				// get filesize before rename. do this before rename so filesize is known even after rename
				$existing_old_image_filesize = filesize(mb_convert_encoding($file_path, 'SJIS')) || 0;
				# rename old file (before redownload the new one)
				$existing_image_date = date('YmdHis', filemtime(mb_convert_encoding($file_path, 'SJIS')));
				$path_parts = pathinfo(mb_convert_encoding($file_path, 'SJIS'));
				$new_file_path = $path_parts['dirname'] . '\\' . $path_parts['filename'] . '_' . $existing_image_date . '.' . $path_parts['extension'];
				rename(mb_convert_encoding($file_path, 'SJIS'), $new_file_path);

				$image = Image::where('thread_id', $thread_id)->where('url', $url)->where('download_status', 1)->first();
				// create new record for modified/false image in case it doesn't exist on database. New image metadata will be created after save success, not here
				if($image == null || $image->count() != 1) {
					$image = new Image();
					$image->thread_id = $thread_id;
					$image->name = $path_parts['filename'] . '_' . $existing_image_date . '.' . $path_parts['extension'];
					$image->url = $url;
					$image->size = $existing_old_image_filesize;
					$image->download_status = 3;
					$image->save();
				}
				else {
					$image->name = $path_parts['filename'] . '_' . $existing_image_date . '.' . $path_parts['extension'];
					$image->download_status = 3;
					$image->save();
				}
				$this->materializeThumbnail($file_path,  'thumbnails/' . $thread_id . '/~thumb_' . $path_parts['filename'] . '_' . $existing_image_date . '.' . $path_parts['extension']);
			}
		}

		// currently commented out as it's questionable whether block checking should be done here, where the function is only to load image per se
		// check if status of url image of the thread is blocked
		// $image_blocked = Image::where('thread_id', $thread_id)->where('url', $url)->whereIn('download_status', array(2))->get();
		// if($image_blocked != null || $image_blocked->count() > 0) {
		// 	throw new \Symfony\Component\HttpKernel\Exception\HttpException('status of ' . $url . ' is on blocked category');
		// }

		$save_success = false;
		for ($iteration=0; $iteration < 3; $iteration++) { 
			$file_path_encoded = mb_convert_encoding($file_path, 'SJIS');
			$written_byte = -1;
			if(config('constant.USE_PROXY')) {
				$written_byte = $this->saveImageWithProxy($url, $file_path_encoded);
			}
			else {
				$written_byte = $this->saveImage($url, $file_path_encoded);
			}
			
			// check if valid image size as source
			if($source_image_size == filesize($file_path_encoded) && $source_image_size == $written_byte) {
				// check if valid image type, as possible source/proxy hiccup that return invalid image
				if(exif_imagetype($file_path_encoded) != false) {
					$save_success = true;
					break;
				}
			}
		}
		// var_dump($written_byte);
		// return;

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

		$this->materializeThumbnail($file_path, 'thumbnails/' . $thread_id . '/~thumb_' . $image_name);

		return response()->json([
			'data' => [
				'id' => $image->id,
				'name' => $image_name,
				'size' => $source_image_size,
				'thumb' => 'thumbnails/' . $thread_id . '/~thumb_' . $image_name . '.jpg',
			]
		]);
	}

	public function exclude($id) {
		$image = Image::find($id);
		$thread = Thread::find($image->thread_id);
		$file_path = $thread->download_directory . '\\' . $image->name;
		// $file_path = mb_convert_encoding($file_path, 'SJIS');

		if(!is_dir('exclusions')) {
			mkdir('exclusions', 777);
		}

		// check if string consists a non-ascii character
		if(preg_match('/[^\x20-\x7f]/', $file_path)) {
			$file_path = mb_convert_encoding($file_path, 'SJIS');
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

		if(!is_dir('deletions')) {
			mkdir('deletions', 777);
		}

		if(preg_match('/[^\x20-\x7f]/', $file_path)) {
			$file_path = mb_convert_encoding($file_path, 'SJIS');
		}

		if(file_exists($file_path)) {
			$ren = rename($file_path, 'deletions/' . $image->name);
			// if(unlink($file_path)) {
			if($ren) {
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
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			if(config('constant.USE_TRUE_PROXY')) {
				curl_setopt($ch, CURLOPT_PROXY, config('constant.TRUE_PROXY_ADRESS'));
			//$proxyauth = 'user:password';
				//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // If url has redirects then go to the final redirected URL.
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
			}
			$raw = curl_exec($ch);
			if($raw == false) {
				throw new \Exception(curl_error($ch), curl_errno($ch));
			}
			curl_close ($ch);

			// $file_path = mb_convert_encoding($file_path, 'SJIS'); // questionable duplicate
			mb_convert_encoding($file_path, 'SJIS'); // questionable duplicate
			$fp = fopen($file_path, 'x');
			$written_byte = fwrite($fp, $raw);
			fclose($fp);
		}
		catch(\Exception $e) {
			// return 0;
			trigger_error(sprintf(
				'curl failed with error #%d: %s',
				$e->getCode(), $e->getMessage()),
			E_USER_ERROR);
		}

		return $written_byte;
	}

	protected function saveImageWithProxy($url, $file_path)
	{
		$responder = config('constant.PROXY_URL') . 'relay/server_visual.php';

		$is_encrypted = false;
		if(config('constant.USE_REQUEST_ENCRYPTION')) {
			$url = $this->encryptString($url);
			$is_encrypted = true;
		}

		// prepare POST string
		$fields = array(
			'url' => $url,
			'is_encrypted' => $is_encrypted
			);
		$postvars = '';
		$sep = ' ';
		foreach ($fields as $key => $value) {
			$postvars .= $sep.urlencode($key).'='.urlencode($value);
			$sep = '&';
		}

		try{
			$agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';
			$ch = curl_init();
			if(false == $ch) {
				throw new \Exception('failed to initialize');
			}
			curl_setopt($ch, CURLOPT_URL, $responder);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			$raw = curl_exec($ch);
			// return $raw;
			if($raw == false) {
				throw new \Exception(curl_error($ch), curl_errno($ch));
			}
			curl_close ($ch);

			// $img = json_decode(trim($raw), TRUE);
			// $file_path = 

			$isEncrypted = true;
			if($isEncrypted) {
				$key = pack('H*', "16a6d7f49404004f737be38f9caec915a411a5380ea1604edbaf34ebc398f6a4");
				$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
				$ciphertext_dec = base64_decode($raw);
				$iv_dec = substr($ciphertext_dec, 0, $iv_size);
				$ciphertext_dec = substr($ciphertext_dec, $iv_size);
				$raw = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
				$raw = base64_decode($raw);
				$raw = rtrim($raw, "\0");
			}

			mb_convert_encoding($file_path, 'SJIS'); // questionable duplicate
			$fp = fopen($file_path, 'w');
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

	protected function retrieveHeaders($url) {
		if(config('constant.USE_PROXY')) {
			$responder = config('constant.PROXY_URL') . 'relay/server_header.php';

			$is_encrypted = false;
			if(config('constant.USE_REQUEST_ENCRYPTION')) {
				$url = $this->encryptString($url);
				$is_encrypted = true;
			}

			$fields = array(
				'url' => $url,
				'is_encrypted' => $is_encrypted
				);
			$postvars = '';
			$sep = ' ';
			foreach ($fields as $key => $value) {
				$postvars .= $sep.urlencode($key).'='.urlencode($value);
				$sep = '&';
			}

			$ch = curl_init();
			$agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';
			curl_setopt($ch, CURLOPT_URL, $responder);
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);
			curl_close($ch);
			$result = json_decode(trim($result), TRUE);
			return $result;
		}
		else {
			
			if(config('constant.USE_TRUE_PROXY')) {
				$curl = curl_init();
				curl_setopt_array(
					$curl,
					array(
						CURLOPT_HEADER => true,
						CURLOPT_NOBODY => true,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_PROXY => config('constant.TRUE_PROXY_ADRESS'),
						CURLOPT_URL => $url
					)
				);
				$headers = explode( "\n", curl_exec($curl));
				curl_close( $curl );
				return $headers;
			}
			else {
				// $agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';

				// $curl = curl_init();
				// curl_setopt_array(
				// 	$curl,
				// 	array(
				// 		CURLOPT_HEADER => true,
				// 		CURLOPT_NOBODY => true,
				// 		CURLOPT_RETURNTRANSFER => true,
				// 		CURLOPT_USERAGENT => $agent,
				// 		CURLOPT_URL => $url
				// 	)
				// );
				// $headers = explode( "\n", curl_exec($curl));
				// curl_close( $curl );
				// return $headers;
				return get_headers($url);
			}

			// $ch = curl_init( $url );
			// $agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';

			// curl_setopt($ch, CURLOPT_URL, $url);
			// curl_setopt($ch, CURLOPT_HEADER, 0);
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// curl_setopt($ch, CURLOPT_USERAGENT, $agent);

			// $raw = curl_exec($ch);
			// // if($raw == false) {
			// // 	print("X");
			// // 	throw new \Exception(curl_error($ch), curl_errno($ch));
			// // }
			// return $raw;

			// $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			// $header = substr($raw, 0, $header_size);
			// return $header;

			return 1;
		}
	}
}
