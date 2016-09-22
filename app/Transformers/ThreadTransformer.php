<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Thread;

class ThreadTransformer extends TransformerAbstract
{
	public function transform(Thread $thread)
	{
		return [
			'id' => $thread->id,
			'name' => $thread->name,
			'url' => $thread->url,
			'status' => $thread->status,
			'download_directory' => $thread->download_directory,
			'last_update' => $thread->last_update,
		];
	}
}