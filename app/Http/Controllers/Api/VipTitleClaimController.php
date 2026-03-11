<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VipTitleClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VipTitleClaimController extends Controller
{
    private const RESERVED_TERMS = ['admin', 'administrator', 'dev', 'developer', 'owner', 'mod', 'moderator', 'staff'];
    private const PROFANITY_TERMS = ['anjing', 'babi', 'bangsat', 'kontol', 'memek', 'ngentot', 'goblok', 'tolol', 'jancok', 'fuck', 'bitch'];

    public function index(Request $request): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $validated = $request->validate([
            'map_key' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = VipTitleClaim::query()->latest('requested_at');

        if (! empty($validated['map_key'])) {
            $query->where('map_key', $validated['map_key']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $claims = $query->take($validated['limit'] ?? 10)->get();

        return response()->json([
            'items' => $claims,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($this->hasBotToken($request), 401);

        $validated = $request->validate([
            'map_key' => ['required', 'string', 'max:64'],
            'gamepass_id' => ['nullable', 'integer', 'min:0'],
            'roblox_user_id' => ['required', 'integer', 'min:1'],
            'roblox_username' => ['required', 'string', 'max:255'],
            'requested_title' => ['required', 'string', 'min:3', 'max:28'],
            'discord_user_id' => ['nullable', 'string', 'max:255'],
            'discord_tag' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        $mapKey = $this->normalizeMapKey($validated['map_key']);
        $title = $this->sanitizeTitle($validated['requested_title']);
        $reason = $this->validateTitle($title);

        if ($reason !== null) {
            return response()->json([
                'message' => $reason,
            ], 422);
        }

        $existingPending = VipTitleClaim::query()
            ->where('map_key', $mapKey)
            ->where('roblox_user_id', $validated['roblox_user_id'])
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        if ($existingPending) {
            $existingPending->update([
                'requested_title' => $title,
                'gamepass_id' => $validated['gamepass_id'] ?? null,
                'discord_user_id' => $validated['discord_user_id'] ?? null,
                'discord_tag' => $validated['discord_tag'] ?? null,
                'meta' => $validated['meta'] ?? null,
                'requested_at' => now(),
            ]);

            return response()->json([
                'claim' => $existingPending->fresh(),
                'updated' => true,
            ]);
        }

        $claim = VipTitleClaim::query()->create([
            'map_key' => $mapKey,
            'gamepass_id' => $validated['gamepass_id'] ?? null,
            'roblox_user_id' => $validated['roblox_user_id'],
            'roblox_username' => $validated['roblox_username'],
            'requested_title' => $title,
            'discord_user_id' => $validated['discord_user_id'] ?? null,
            'discord_tag' => $validated['discord_tag'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
            'meta' => $validated['meta'] ?? null,
        ]);

        return response()->json([
            'claim' => $claim,
            'created' => true,
        ], 201);
    }

    public function pull(Request $request): JsonResponse
    {
        abort_unless($this->hasRobloxApiKey($request), 401);

        $validated = $request->validate([
            'userId' => ['required', 'integer', 'min:1'],
            'username' => ['nullable', 'string', 'max:255'],
            'mapKey' => ['required', 'string', 'max:64'],
            'placeId' => ['nullable', 'string', 'max:255'],
            'universeId' => ['nullable', 'string', 'max:255'],
        ]);

        $claim = VipTitleClaim::query()
            ->where('roblox_user_id', $validated['userId'])
            ->where('map_key', $this->normalizeMapKey($validated['mapKey']))
            ->where('status', 'pending')
            ->oldest('requested_at')
            ->first();

        if (! $claim) {
            return response()->json([
                'claim' => null,
            ]);
        }

        return response()->json([
            'claim' => [
                'claimId' => $claim->id,
                'title' => $claim->requested_title,
                'mapKey' => $claim->map_key,
                'gamepassId' => $claim->gamepass_id,
                'requestedAt' => $claim->requested_at,
                'placeId' => $validated['placeId'] ?? null,
                'universeId' => $validated['universeId'] ?? null,
            ],
        ]);
    }

    public function consume(Request $request): JsonResponse
    {
        abort_unless($this->hasRobloxApiKey($request), 401);

        $validated = $request->validate([
            'claimId' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:255'],
            'mapKey' => ['nullable', 'string', 'max:64'],
            'placeId' => ['nullable', 'string', 'max:255'],
            'universeId' => ['nullable', 'string', 'max:255'],
        ]);

        $claim = VipTitleClaim::query()->findOrFail($validated['claimId']);

        if (! empty($validated['mapKey']) && $claim->map_key !== $this->normalizeMapKey($validated['mapKey'])) {
            return response()->json([
                'message' => 'Map key claim tidak cocok.',
            ], 422);
        }

        $claim->update([
            'status' => $validated['status'],
            'consumed_at' => now(),
            'consumed_place_id' => $validated['placeId'] ?? null,
            'consumed_universe_id' => $validated['universeId'] ?? null,
            'meta' => array_filter([
                ...($claim->meta ?? []),
                'consume_reason' => $validated['reason'] ?? null,
            ], static fn ($value) => $value !== null),
        ]);

        return response()->json([
            'ok' => true,
            'status' => $claim->status,
        ]);
    }

    private function hasBotToken(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Bot-Token'));
    }

    private function hasRobloxApiKey(Request $request): bool
    {
        $token = config('services.discord.internal_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Api-Key'));
    }

    private function normalizeMapKey(string $value): string
    {
        return strtolower(trim($value));
    }

    private function sanitizeTitle(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $value)) ?? '');
    }

    private function normalizeText(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/', '', $value) ?? '');
    }

    private function validateTitle(string $title): ?string
    {
        $normalized = $this->normalizeText($title);

        foreach (self::RESERVED_TERMS as $term) {
            if (str_contains($normalized, $this->normalizeText($term))) {
                return sprintf('Title tidak boleh memakai kata seperti "%s".', $term);
            }
        }

        foreach (self::PROFANITY_TERMS as $term) {
            if (str_contains($normalized, $this->normalizeText($term))) {
                return 'Title mengandung kata yang tidak diperbolehkan.';
            }
        }

        return null;
    }
}
