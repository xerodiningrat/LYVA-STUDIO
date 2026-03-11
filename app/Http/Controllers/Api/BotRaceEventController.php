<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RaceEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotRaceEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $events = RaceEvent::query()
            ->withCount('participants')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'items' => $events,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'max_players' => ['required', 'integer', 'min:2', 'max:100'],
            'entry_fee_robux' => ['nullable', 'integer', 'min:0'],
            'created_by_discord_id' => ['nullable', 'string', 'max:255'],
            'created_by_name' => ['nullable', 'string', 'max:255'],
            'registration_closes_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $event = RaceEvent::query()->create([
            ...$validated,
            'entry_fee_robux' => $validated['entry_fee_robux'] ?? 0,
            'status' => 'registration_open',
        ]);

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'status' => $event->status,
        ], 201);
    }

    public function show(Request $request, RaceEvent $event): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $event->loadCount('participants');

        return response()->json($event);
    }

    public function update(Request $request, RaceEvent $event): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'registration_closes_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $event->fill($validated);
        $event->save();

        return response()->json([
            'id' => $event->id,
            'title' => $event->title,
            'status' => $event->status,
            'registration_closes_at' => $event->registration_closes_at,
            'starts_at' => $event->starts_at,
            'meta' => $event->meta,
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
