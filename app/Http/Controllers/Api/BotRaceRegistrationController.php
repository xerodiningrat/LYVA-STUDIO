<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RaceEvent;
use App\Models\RaceParticipant;
use App\Services\Roblox\RobloxUserLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

class BotRaceRegistrationController extends Controller
{
    public function store(
        Request $request,
        RaceEvent $event,
        RobloxUserLookupService $robloxUserLookupService,
    ): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        if ($event->status !== 'registration_open') {
            return response()->json(['message' => 'Registrasi event sudah ditutup.'], 422);
        }

        if ($event->participants()->count() >= $event->max_players) {
            return response()->json(['message' => 'Slot event sudah penuh.'], 422);
        }

        $validated = $request->validate([
            'discord_user_id' => ['required', 'string', 'max:255'],
            'discord_username' => ['required', 'string', 'max:255'],
            'roblox_username' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $robloxUser = $robloxUserLookupService->findByUsername($validated['roblox_username']);
        } catch (RequestException $exception) {
            return response()->json([
                'message' => 'Validasi username Roblox gagal. Coba lagi sebentar lagi.',
            ], 502);
        }

        if ($robloxUser === null) {
            return response()->json([
                'message' => 'Username Roblox tidak ditemukan. Periksa lagi ejaan username-nya.',
            ], 422);
        }

        $participant = RaceParticipant::query()->firstOrCreate(
            [
                'race_event_id' => $event->id,
                'discord_user_id' => $validated['discord_user_id'],
            ],
            [
                'discord_username' => $validated['discord_username'],
                'roblox_username' => $robloxUser['name'],
                'status' => 'registered',
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return response()->json([
            'participant_id' => $participant->id,
            'race_event_id' => $event->id,
            'status' => $participant->status,
            'roblox_username' => $participant->roblox_username,
            'roblox_user_id' => $robloxUser['id'],
            'roblox_display_name' => $robloxUser['display_name'],
        ], $participant->wasRecentlyCreated ? 201 : 200);
    }

    private function hasValidToken(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Bot-Token'));
    }
}
