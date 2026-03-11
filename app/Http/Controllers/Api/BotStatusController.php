<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscordWebhook;
use App\Models\PlatformAlert;
use App\Models\PlayerReport;
use App\Models\RobloxGame;
use App\Models\SalesEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BotStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($this->hasValidToken($request), 401);

        $hasBotTables = Schema::hasTable('roblox_games')
            && Schema::hasTable('discord_webhooks')
            && Schema::hasTable('platform_alerts')
            && Schema::hasTable('player_reports');

        return response()->json([
            'ok' => true,
            'has_bot_tables' => $hasBotTables,
            'tracked_games' => $hasBotTables ? RobloxGame::count() : 0,
            'active_webhooks' => $hasBotTables ? DiscordWebhook::query()->where('is_active', true)->count() : 0,
            'open_alerts' => $hasBotTables ? PlatformAlert::query()->whereIn('status', ['open', 'investigating'])->count() : 0,
            'pending_reports' => $hasBotTables ? PlayerReport::query()->whereIn('status', ['new', 'triaged'])->count() : 0,
            'sales_events' => Schema::hasTable('sales_events') ? SalesEvent::count() : 0,
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
