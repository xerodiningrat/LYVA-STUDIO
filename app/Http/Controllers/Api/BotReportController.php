<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotReportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $validated = $request->validate([
            'reporter_name' => ['required', 'string', 'max:255'],
            'reported_player_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'summary' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'max:50'],
            'payload' => ['nullable', 'array'],
        ]);

        $report = PlayerReport::query()->create([
            'reporter_name' => $validated['reporter_name'],
            'reported_player_name' => $validated['reported_player_name'],
            'category' => $validated['category'],
            'summary' => $validated['summary'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'new',
            'payload' => $validated['payload'] ?? ['source' => 'discord_gateway_bot'],
        ]);

        return response()->json([
            'id' => $report->id,
            'status' => $report->status,
        ], 201);
    }

    private function hasValidToken(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Bot-Token'));
    }
}
