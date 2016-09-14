<?php

use Illuminate\Database\Seeder;

use App\Site;
use App\Thread;
use App\Image;
use App\State;
use App\Suffix;

class DatabaseSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		// $this->call(UsersTableSeeder::class);

		DB::table('sites')->delete();
		$sites = array(
			[
				'id' => '1',
				'name' => 'deviantart',
				'domain' => 'deviantart.com',
			],
			[
				'id' => '2',
				'name' => 'safebooru',
				'domain' => 'safebooru.org',
			],
		);

		foreach($sites as $site) {
			Site::create($site);
		}

		DB::table('threads')->delete();
		$threads = array(
			[
				'id' => '1',
				'site_id' => '1',
				'name' => 'manga',
				'url' => 'http://www.deviantart.com/browse/all/manga',
				'status' => '1',
			],
			[
				'id' => '2',
				'site_id' => '2',
				'name' => 'browse',
				'url' => 'http://www.deviantart.com/browse/all/manga',
				'status' => '1',
			],
		);

		foreach($threads as $thread) {
			Thread::create($thread);
		}

		DB::table('states')->delete();
		$states = array(
			[
				'id' => '1',
				'thread_id' => '1',
				'download_directory' => 'C:/downloadingrep',
				'last_update' => '2016-09-13 01:02:03',
				'status' => '1',
			],
		);

		foreach ($states as $state) {
			State::create($state);
		}

	}
}
