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
	$api->group(['prefix' => 'thread'], function($api) {
		$api->get('/', 'App\Http\Controllers\ThreadController@index');
		$api->post('/new', 'App\Http\Controllers\ThreadController@newInstance');
		$api->put('/{id}', 'App\Http\Controllers\ThreadController@update');
		$api->delete('/{id}', 'App\Http\Controllers\ThreadController@delete');
		$api->get('/{id}/refresh', 'App\Http\Controllers\ThreadController@refresh');
		$api->get('/{id}/images', 'App\Http\Controllers\ThreadController@getSavedImages');
		$api->get('/{id}/load', 'App\Http\Controllers\ThreadController@loadNewImagesList');
	});

	$api->group(['prefix' => 'image'], function($api) {
		$api->post('/load', 'App\Http\Controllers\ImageController@load');
		$api->post('/metaOnly', 'App\Http\Controllers\ImageController@createMeta');
		$api->delete('/{id}/block', 'App\Http\Controllers\ImageController@block');
		$api->delete('/{id}/exclude', 'App\Http\Controllers\ImageController@exclude');
	});

	$api->group(['prefix' => 'suffix'], function($api) {
		$api->get('/', 'App\Http\Controllers\SuffixController@getSuffixes');
		$api->post('/', 'App\Http\Controllers\SuffixController@create');
		$api->delete('/{id}', 'App\Http\Controllers\SuffixController@delete');
	});
});