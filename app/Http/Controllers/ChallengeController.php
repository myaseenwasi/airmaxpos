<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChallengeController extends Controller
{
    public function home()
    {
        return "Hello, Laravel!";
    }

    public function challenge(Request $request)
    {
		$rawData = $request->getContent();
    	Log::info('Raw Request Body: ' . $rawData);
        $data = $request->getContent();
        Log::info('ChallengeController@challenge received data', ['data' => $data]);
        return response($data, 200);
    }
} 