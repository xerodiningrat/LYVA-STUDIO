<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RulesAcknowledgement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotRulesAcknowledgementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $validated = $request->validate([
            'guild_id' => ['nullable', 'string', 'max:255'],
            'channel_id' => ['required', 'string', 'max:255'],
            'message_id' => ['required', 'string', 'max:255'],
            'discord_user_id' => ['required', 'string', 'max:255'],
            'discord_username' => ['required', 'string', 'max:255'],
        ]);

        $acknowledgement = RulesAcknowledgement::query()->firstOrCreate(
            [
                'message_id' => $validated['message_id'],
                'discord_user_id' => $validated['discord_user_id'],
            ],
            $validated,
        );

        $acknowledgements = RulesAcknowledgement::query()
            ->where('message_id', $validated['message_id'])
            ->latest()
            ->get(['discord_user_id', 'discord_username']);

        return response()->json([
            'created' => $acknowledgement->wasRecentlyCreated,
            'total' => $acknowledgements->count(),
            'users' => $acknowledgements->map(fn (RulesAcknowledgement $item) => [
                'discord_user_id' => $item->discord_user_id,
                'discord_username' => $item->discord_username,
            ])->all(),
        ], $acknowledgement->wasRecentlyCreated ? 201 : 200);
    }

    private function hasValidToken(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Bot-Token'));
    }
}
