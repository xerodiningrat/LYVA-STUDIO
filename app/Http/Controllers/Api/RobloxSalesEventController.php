<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RobloxGame;
use App\Models\SalesEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RobloxSalesEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $validated = $request->validate([
            'universe_id' => ['nullable', 'string', 'max:255'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', 'max:50'],
            'product_id' => ['nullable', 'string', 'max:255'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'amount_robux' => ['required', 'integer', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'purchased_at' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
        ]);

        $gameId = null;

        if (! empty($validated['universe_id'])) {
            $gameId = RobloxGame::query()
                ->where('universe_id', $validated['universe_id'])
                ->value('id');
        }

        $event = SalesEvent::query()->create([
            'roblox_game_id' => $gameId,
            'product_name' => $validated['product_name'],
            'product_type' => $validated['product_type'],
            'product_id' => $validated['product_id'] ?? null,
            'buyer_name' => $validated['buyer_name'],
            'amount_robux' => $validated['amount_robux'],
            'quantity' => $validated['quantity'] ?? 1,
            'purchased_at' => $validated['purchased_at'] ?? now(),
            'payload' => $validated['payload'] ?? [],
        ]);

        return response()->json([
            'id' => $event->id,
            'stored' => true,
        ], 201);
    }

    private function hasValidToken(Request $request): bool
    {
        $token = config('services.roblox.ingest_token');

        return is_string($token)
            && $token !== ''
            && hash_equals($token, (string) $request->header('X-Roblox-Token'));
    }
}
