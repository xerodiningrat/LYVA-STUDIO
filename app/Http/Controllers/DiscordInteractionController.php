<?php

namespace App\Http\Controllers;

use App\Services\Discord\DiscordInteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscordInteractionController extends Controller
{
    public function __invoke(Request $request, DiscordInteractionService $interactions): JsonResponse
    {
        abort_unless($interactions->verifySignature($request), 401, 'Invalid Discord signature.');

        return $interactions->handle($request);
    }
}
