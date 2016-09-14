<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\State;

class StateController extends Controller
{
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

		return $state->toArray();
	}
}
