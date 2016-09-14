<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\State;

class StateTransformer extends TransformerAbstract
{
	public function transform(State $state)
	{
		return [
			'download_directory' => $state->download_directory,
			'last_update' => $state->last_update,
			'status' => $state->state_status,
		];
	}
}