<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscordVerification;
use App\Services\Roblox\RobloxUserLookupService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotVerificationController extends Controller
{
    public function show(Request $request, string $discordUserId): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $verification = DiscordVerification::query()
            ->where('discord_user_id', $discordUserId)
            ->first();

        if (! $verification) {
            return response()->json([
                'verified' => false,
            ]);
        }

        return response()->json([
            'verified' => true,
            'discord_user_id' => $verification->discord_user_id,
            'discord_username' => $verification->discord_username,
            'roblox_user_id' => $verification->roblox_user_id,
            'roblox_username' => $verification->roblox_username,
            'roblox_display_name' => $verification->roblox_display_name,
            'verified_at' => $verification->verified_at,
        ]);
    }

    public function store(
        Request $request,
        RobloxUserLookupService $robloxUserLookupService,
    ): JsonResponse {
        abort_unless($this->hasValidToken($request), 401);

        $validated = $request->validate([
            'guild_id' => ['nullable', 'string', 'max:255'],
            'discord_user_id' => ['required', 'string', 'max:255'],
            'discord_username' => ['required', 'string', 'max:255'],
            'roblox_username' => ['required', 'string', 'max:255'],
        ]);

        try {
            $robloxUser = $robloxUserLookupService->findByUsername($validated['roblox_username']);
        } catch (RequestException) {
            return response()->json([
                'message' => 'Validasi username Roblox gagal. Coba lagi sebentar lagi.',
            ], 502);
        }

        if ($robloxUser === null) {
            return response()->json([
                'message' => 'Username Roblox tidak ditemukan. Periksa lagi ejaan username-nya.',
            ], 422);
        }

        $existingByRoblox = DiscordVerification::query()
            ->where('roblox_user_id', $robloxUser['id'])
            ->where('discord_user_id', '!=', $validated['discord_user_id'])
            ->first();

        if ($existingByRoblox) {
            return response()->json([
                'message' => 'Akun Roblox itu sudah terhubung ke akun Discord lain.',
            ], 422);
        }

        $verification = DiscordVerification::query()->updateOrCreate(
            ['discord_user_id' => $validated['discord_user_id']],
            [
                'guild_id' => $validated['guild_id'] ?? null,
                'discord_username' => $validated['discord_username'],
                'roblox_user_id' => $robloxUser['id'],
                'roblox_username' => $robloxUser['name'],
                'roblox_display_name' => $robloxUser['display_name'],
                'verified_at' => now(),
            ],
        );

        return response()->json([
            'verified' => true,
            'discord_user_id' => $verification->discord_user_id,
            'discord_username' => $verification->discord_username,
            'roblox_user_id' => $verification->roblox_user_id,
            'roblox_username' => $verification->roblox_username,
            'roblox_display_name' => $verification->roblox_display_name,
            'verified_at' => $verification->verified_at,
        ], 201);
    }

    public function destroy(Request $request, string $discordUserId): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        DiscordVerification::query()
            ->where('discord_user_id', $discordUserId)
            ->delete();

        return response()->json([
            'unlinked' => true,
        ]);
    }

    private function hasValidToken(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Bot-Token'));
    }
}
