<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Suffix;

class SuffixController extends Controller
{
	public function __construct() {}

	public function getSuffixes() {
		try {
			$suffixes = Suffix::orderBy('name')->get();
		} catch (\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Get failed');
		}

		return response()->json([
			'data' => $suffixes,
		]);
	}

	public function create(Request $request) {
		try {
			$suffix = new Suffix();
			$suffix->name = $request->name;
			$suffix->save();
		} catch (\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Create failed');
		}

		return response()->json([
			'data' => $suffix,
		]);
	}

	public function delete($id) {
		try {
			$suffix = Suffix::find($id);
			$suffix->delete();
		} catch (\Exception $e) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException('Delete failed');
		}

		return response()->json(['meta' => ['message' => 'success', 'status_code' => 200]]);
	}
}
