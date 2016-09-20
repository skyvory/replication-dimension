<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\State;
use App\Site;
use App\Thread;
use App\Transformers\StateTransformer;
use App\Transformers\NewStateReturnTransformer;
use Dingo\Api\Routing\Helpers;
use App\Parsers\ParserManager;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StateController extends Controller
{
	use Helpers;
	use ParserManager;
	public function __construct() {}

	public function index()
	{
		$state = State::join('threads', 'threads.id', '=', 'states.thread_id')
			->select('states.id', 'states.download_directory', 'states.last_update', 'states.status as state_status',
				'threads.name', 'threads.url', 'threads.status as thread_status'
				)
			->where('states.status', 1)
			->orderBy('states.created_at', 'asc')
			->get();

		// $plug = $this->getParserBridge();
		// return $plug;

		return $state->toArray();
		return $this->response->collection($state, new StateTransformer);
	}

	public function newInstance(Request $request)
	{
		// >>> to add validator

		$url = $request->input('url');
		$download_directory = $request->input('download_directory');

		// avoid creating duplicate state
		if($this->isDuplicateState($url)) {
			throw new ConflictHttpException('Unable to create duplicate state!');
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
			$exec = $thread->save();

			$state = new State();
			$state->thread_id = $thread->id;
			$state->download_directory = $download_directory;
			$state->last_update = date('Y-m-d H:i:s');
			$state->status = 1;
			$state->save();

			\DB::commit();
		}
		catch(Exception $e) {
			\DB::rollback();
			throw($e);
		}

		$images = $this->parseThreadContent($site_name, $html_content);

		$compact = array(
			'data' => array(
				'thread' => array(
					'name' => $thread_name
					),
				'state' => array(
					'id' => $state->id
					),
				'images' => $images
				)
			);


		return $this->response->array($compact);

	}

	protected function isDuplicateState($url) {
		$existingStateCount = State::join('threads', 'threads.id', '=', 'states.thread_id')->where('url', $url)->where('states.status', 1)->get()->count();
		if($existingStateCount > 0) {
			return true;
		}
		else {
			return false;
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
