<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BotSalesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        return match ($request->query('mode', 'live')) {
            'summary' => $this->summary(),
            default => $this->live(),
        };
    }

    private function live(): JsonResponse
    {
        $events = SalesEvent::query()
            ->latest('purchased_at')
            ->take(5)
            ->get([
                'id',
                'product_name',
                'product_type',
                'buyer_name',
                'amount_robux',
                'quantity',
                'purchased_at',
            ]);

        return response()->json([
            'items' => $events,
        ]);
    }

    private function summary(): JsonResponse
    {
        $windowStart = now()->subDay();

        $baseQuery = SalesEvent::query()->where('purchased_at', '>=', $windowStart);

        $topProduct = (clone $baseQuery)
            ->select('product_name', DB::raw('SUM(quantity) as sold_count'), DB::raw('SUM(amount_robux * quantity) as robux_total'))
            ->groupBy('product_name')
            ->orderByDesc('robux_total')
            ->first();

        return response()->json([
            'window' => '24h',
            'transactions' => (clone $baseQuery)->count(),
            'robux_total' => (clone $baseQuery)->selectRaw('COALESCE(SUM(amount_robux * quantity), 0) as total')->value('total'),
            'top_product' => $topProduct,
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
