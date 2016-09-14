<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');

// Route::group(['prefix' => '0'], function() {
// 	Route::group(['prefix' => 'state'], function() {
// 		Route::get('/', 'StateController@index');
// 		Route::post('/create', 'StateController@create');
// 		Route::put('/{id}', 'StateController@update');
// 		Route::delete('/{id}', 'StateController@delete');
// 	});
// });

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [], function($api) {
	$api->group(['prefix' => 'state'], function($api) {
		$api->get('/', 'App\Http\Controllers\StateController@index');
		// $api->post('/create', 'StateController@create');
		// $api->put('/{id}', 'StateController@update');
		// $api->delete('/{id}', 'StateController@delete');
	});
	$api->get('test', function() {
		return 'Ok';
	});
});