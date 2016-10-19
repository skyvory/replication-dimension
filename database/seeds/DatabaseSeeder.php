<?php

use Illuminate\Database\Seeder;

use App\Site;
use App\Thread;
use App\Image;
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
				'name' => 'Deviantart',
				'domain' => 'deviantart.com',
			],
			[
				'id' => '2',
				'name' => 'Safebooru',
				'domain' => 'safebooru.org',
			],
			[
				'id' => '3',
				'name' => '2chan',
				'domain' => '2chan.net',
			],
		);

		foreach($sites as $site) {
			Site::create($site);
		}

	}
}
