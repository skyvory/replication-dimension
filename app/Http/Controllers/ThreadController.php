<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Site;
use App\Thread;
use App\Image;
use App\Transformers\ThreadTransformer;
use Dingo\Api\Routing\Helpers;
use App\Parsers\ParserManager;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use App\Http\Controllers\Writer;

class ThreadController extends Controller
{
	use Helpers;
	use ParserManager;
	use Writer;
	public function __construct() {}

	public function index()
	{
		$threads = Thread::where('status', '!=', 4)
			->orderBy('created_at', 'asc')
			->get();

		return $this->response->collection($threads, new ThreadTransformer);
	}

	public function newInstance(Request $request)
	{
		// >>> to add validator

		$url = $request->input('url');
		$download_directory = $request->input('download_directory');

		// avoid creating duplicate state
		if($this->isDuplicateThread($url)) {
			// throw new ConflictHttpException('Unable to create duplicate state!');
		}

		// retrieve record about the site
		$site = $this->getSiteMatch($url);
		$site_id = $site['id'];
		$site_name = $site['name'];


		// $mock_html_content = file_get_contents(public_path() . '\dev.html');
		$html_content = $this->getHtmlContent($url);
		$thread_name = $this->getSiteTitle($html_content);

		\DB::beginTransaction();

		try {
			$thread = new Thread();
			$thread->site_id = $site_id;
			$thread->name = $thread_name;
			$thread->url = $url;
			$thread->status = 1;
			$thread->download_directory = $download_directory;
			$thread->last_update = date('Y-m-d H:i:s');
			$exec = $thread->save();

			\DB::commit();
		}
		catch(Exception $e) {
			\DB::rollback();
			throw($e);
		}

		$this->writeHtmlToDisk($html_content, $download_directory);

		$images = $this->parseThreadContent($site_name, $html_content);

		$compact = array(
			'data' => array(
				'thread' => array(
					'id' => $thread->id,
					'name' => $thread_name
					),
				'images' => $images
				)
			);


		return $this->response->array($compact);

	}

	public function update(Request $request, $id) {
		$download_directory = $request->download_directory;

		$thread = Thread::find($id);
		$thread->download_directory = $download_directory;
		$exec = $thread->save();

		if(!$exec) {
			throw new ConflictHttpException('Update failed!');
		}

		return response()->json(['meta' => ['message' => 'success', 'status_code' => 200]]);
	}

	public function delete($id) {
		try {
			$thread = Thread::find($id);
			$thread->status = 4;
			$thread->save();
		} catch (\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Delete failed');
		}

		return response()->json(['meta' => ['message' => 'success', 'status_code' => 200]]);
	}

	public function refresh($id) {
		try {
			$thread = Thread::find($id);

			if($thread->status == 3) {
				throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Thread is already marked as closed');
			}

			$html_content = $this->getHtmlContent($thread->url);
			$site_name = $this->getSiteMatch($thread->url)['name'];
			$images = $this->parseThreadContent($site_name, $html_content);

			return response()->json([
				'data' => [
					'thread' => [
						'url' => $thread->url,
						'status' => $thread->thread_status,
						'download_directory' => $thread->download_directory,
					],
					'images' => $images
				]
			]);
		} catch (Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Refresh failed');
		}
	}

	public function getSavedImages($id) {
		$images = Image::where('thread_id', $id)->get();
		return response()->json(['data' => ['images' => $images]]);
	}

	public function loadNewImagesList($id) {
		$existing_images = Image::where('thread_id', $id)->get();
		$existing_images_urls = array();
		foreach ($existing_images as $img) {
			$existing_images_urls[] = $img['url'];
		}

		$thread = Thread::find($id);
		$site_name = $this->getSiteMatch($thread->url)['name'];
		$html_content = $this->getHtmlContent($thread->url);
		$images = $this->parseThreadContent($site_name, $html_content);

		$new_images_stack = array();
		foreach ($images as $image) {
			if(!in_array($image, $existing_images_urls)) {
				$new_images_stack[] = $image;
			}
		}

		return response()->json(['data' => ['images' => $new_images_stack]]);
	}

	protected function isDuplicateThread($url) {
		try {
			$existingThreadCount = Thread::where('url', $url)->whereIn('.status', array(1,2,3))->get()->count();
			if($existingThreadCount > 0) {
				return true;
			}
			else {
				return false;
			}
		} catch (\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Failed to check for duplicate!');
		}
		
	}

	protected function excerptMainUrl($url) {
		$trim = array('http://', 'https://');
		foreach ($trim as $tr) {
			if(strpos($url, $tr) === 0) {
				return str_replace($tr, '', $url);
			}
		}
		return $url;
	}

	protected function getSiteTitle($html) {
		$str = trim(preg_replace('/\s+/', ' ', $html)); // supports line breaks inside <title>
		preg_match("/\<title\>(.*)\<\/title\>/i", $str, $title); // ignore case
		return $title[1];
	}

	protected function getHtmlContent($url) {
		$agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';
		// $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		$content = curl_init();
		curl_setopt($content, CURLOPT_URL, $url);
		curl_setopt($content, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($content, CURLOPT_USERAGENT, $agent);
		$result = curl_exec($content);
		curl_close($content);

		return $result;
	}

	protected function getSiteMatch($url) {
		$domain = $this->excerptDomain($url);
		$site = Site::where('domain', $domain)->first();
		return $site;
	}

	protected function excerptDomain($url) {
		$urlMap = array('com', 'net', 'co.uk', 'org', 'co.jp', 'goo.ne');

		$host = "";
		$urlData = parse_url($url);
		$hostData = explode('.', $urlData['host']);
		$hostData = array_reverse($hostData);

		if(array_search($hostData[1] . '.' . $hostData[0], $urlMap) !== FALSE) {
			$host = $hostData[2] . '.' . $hostData[1] . '.' . $hostData[0];
		}
		else if(array_search($hostData[0], $urlMap) !== FALSE) {
			$host = $hostData[1] . '.' . $hostData[0];
		}

		return $host;
	}
}
