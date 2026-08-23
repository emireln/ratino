<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Utils\APIResponse;
use App\Whisper\Whisper;
use Illuminate\Http\Request;

class WhisperController extends Controller {

    public function whisper(Request $request) {
        $request->validate(['event' => ['required', 'string'], 'data' => ['nullable', 'array']]);

        $whisper = Whisper::find($request->event);
        if (!$whisper) return APIResponse::reject(1, 'Unsupported game action');

        $whisper->user = auth('sanctum')->user();
        return APIResponse::success($whisper->process((object) ($request->data ?? [])));
    }

}
