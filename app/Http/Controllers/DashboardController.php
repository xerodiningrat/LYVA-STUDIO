<?php

namespace App\Http\Controllers;

use App\Models\DiscordWebhook;
use App\Models\PlatformAlert;
use App\Models\PlayerReport;
use App\Models\RaceEvent;
use App\Models\RobloxGame;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $hasBotTables = Schema::hasTable('roblox_games')
            && Schema::hasTable('discord_webhooks')
            && Schema::hasTable('platform_alerts')
            && Schema::hasTable('player_reports');

        $stats = [
            [
                'label' => 'Tracked games',
                'value' => $hasBotTables ? RobloxGame::count() : 0,
                'hint' => 'Universe dan place yang terhubung ke workspace tim.',
            ],
            [
                'label' => 'Active webhooks',
                'value' => $hasBotTables ? DiscordWebhook::query()->where('is_active', true)->count() : 0,
                'hint' => 'Webhook Discord yang siap kirim alert atau deploy log.',
            ],
            [
                'label' => 'Open alerts',
                'value' => $hasBotTables ? PlatformAlert::query()->whereIn('status', ['open', 'investigating'])->count() : 0,
                'hint' => 'Insiden operasional yang masih perlu tindakan.',
            ],
            [
                'label' => 'Pending reports',
                'value' => $hasBotTables ? PlayerReport::query()->whereIn('status', ['new', 'triaged'])->count() : 0,
                'hint' => 'Laporan player atau bug yang belum selesai.',
            ],
            [
                'label' => 'Race events',
                'value' => Schema::hasTable('race_events') ? RaceEvent::query()->where('status', 'registration_open')->count() : 0,
                'hint' => 'Event balap komunitas yang sedang buka registrasi.',
            ],
        ];

        $games = $hasBotTables
            ? RobloxGame::query()->latest('updated_at')->take(3)->get()
            : collect();

        if ($games->isEmpty()) {
            $games = collect([
                (object) [
                    'name' => 'Blade Arena',
                    'status' => 'healthy',
                    'last_synced_at' => now()->subMinutes(3),
                    'universe_id' => '184420001',
                    'place_id' => '87210055',
                ],
                (object) [
                    'name' => 'Trade Plaza X',
                    'status' => 'monitoring',
                    'last_synced_at' => now()->subMinutes(11),
                    'universe_id' => '184420002',
                    'place_id' => '87210056',
                ],
                (object) [
                    'name' => 'Dungeon Rush',
                    'status' => 'degraded',
                    'last_synced_at' => now()->subMinutes(19),
                    'universe_id' => '184420003',
                    'place_id' => '87210057',
                ],
            ]);
        }

        $alerts = $hasBotTables
            ? PlatformAlert::query()->latest('occurred_at')->take(4)->get()
            : collect();

        if ($alerts->isEmpty()) {
            $alerts = collect([
                (object) [
                    'severity' => 'critical',
                    'title' => 'Studio publish gagal',
                    'message' => 'Webhook deploy mendeteksi publish timeout pada place utama.',
                    'source' => 'deploy',
                    'status' => 'investigating',
                    'occurred_at' => now()->subMinutes(18),
                ],
                (object) [
                    'severity' => 'warning',
                    'title' => 'Revenue drop 27%',
                    'message' => 'Penjualan dev product turun dibanding rolling average 3 jam.',
                    'source' => 'sales',
                    'status' => 'open',
                    'occurred_at' => now()->subHour(),
                ],
                (object) [
                    'severity' => 'info',
                    'title' => 'Badge baru dipublish',
                    'message' => 'Badge seasonal berhasil terdeteksi dan di-log ke Discord.',
                    'source' => 'badge',
                    'status' => 'resolved',
                    'occurred_at' => now()->subHours(2),
                ],
                (object) [
                    'severity' => 'warning',
                    'title' => 'Server shutdown spike',
                    'message' => 'Jumlah shutdown mendadak naik pada region Asia.',
                    'source' => 'servers',
                    'status' => 'open',
                    'occurred_at' => now()->subHours(4),
                ],
            ]);
        }

        $reports = $hasBotTables
            ? PlayerReport::query()->latest()->take(4)->get()
            : collect();

        if ($reports->isEmpty()) {
            $reports = collect([
                (object) [
                    'reporter_name' => 'Aqila',
                    'reported_player_name' => 'ShadowVex',
                    'category' => 'exploit',
                    'priority' => 'high',
                    'status' => 'triaged',
                    'summary' => 'Speed exploit di ranked arena, sudah ada video.',
                ],
                (object) [
                    'reporter_name' => 'Dev QA',
                    'reported_player_name' => 'Matchmaking',
                    'category' => 'bug',
                    'priority' => 'medium',
                    'status' => 'new',
                    'summary' => 'Party queue stuck setelah teleport gagal.',
                ],
                (object) [
                    'reporter_name' => 'Nadim',
                    'reported_player_name' => 'TradeBot12',
                    'category' => 'scam',
                    'priority' => 'high',
                    'status' => 'investigating',
                    'summary' => 'Dugaan penipuan item trade lewat DM Discord.',
                ],
                (object) [
                    'reporter_name' => 'Mira',
                    'reported_player_name' => 'UI Shop',
                    'category' => 'ux',
                    'priority' => 'low',
                    'status' => 'resolved',
                    'summary' => 'Harga game pass tidak refresh setelah pembelian.',
                ],
            ]);
        }

        $webhooks = $hasBotTables
            ? DiscordWebhook::query()->latest('updated_at')->take(3)->get()
            : collect();

        if ($webhooks->isEmpty()) {
            $webhooks = collect([
                (object) [
                    'name' => 'Deploy Feed',
                    'channel_name' => '#deploy-log',
                    'is_active' => true,
                    'last_delivered_at' => now()->subMinutes(6),
                ],
                (object) [
                    'name' => 'Sales Pulse',
                    'channel_name' => '#sales-alerts',
                    'is_active' => true,
                    'last_delivered_at' => now()->subMinutes(14),
                ],
                (object) [
                    'name' => 'Community Desk',
                    'channel_name' => '#player-reports',
                    'is_active' => false,
                    'last_delivered_at' => now()->subDay(),
                ],
            ]);
        }

        $races = Schema::hasTable('race_events')
            ? RaceEvent::query()->withCount('participants')->latest()->take(3)->get()
            : collect();

        if ($races->isEmpty()) {
            $races = collect([
                (object) [
                    'id' => 12,
                    'title' => 'Sprint Night Jakarta',
                    'status' => 'registration_open',
                    'participants_count' => 5,
                    'max_players' => 8,
                    'entry_fee_robux' => 25,
                ],
                (object) [
                    'id' => 11,
                    'title' => 'Weekend Drift Cup',
                    'status' => 'registration_open',
                    'participants_count' => 11,
                    'max_players' => 16,
                    'entry_fee_robux' => 50,
                ],
                (object) [
                    'id' => 10,
                    'title' => 'Admin Test Heat',
                    'status' => 'draft',
                    'participants_count' => 0,
                    'max_players' => 6,
                    'entry_fee_robux' => 0,
                ],
            ]);
        }

        return view('dashboard', [
            'stats' => $stats,
            'games' => $games,
            'alerts' => $alerts,
            'reports' => $reports,
            'webhooks' => $webhooks,
            'races' => $races,
            'hasBotTables' => $hasBotTables,
        ]);
    }
}
