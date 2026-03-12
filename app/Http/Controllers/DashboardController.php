<?php

namespace App\Http\Controllers;

use App\Models\DiscordWebhook;
use App\Models\PlatformAlert;
use App\Models\PlayerReport;
use App\Models\RaceEvent;
use App\Models\RobloxGame;
use App\Services\VipTitleWalletService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, VipTitleWalletService $walletService): View|RedirectResponse
    {
        if (auth()->user()?->discord_user_id && ! $request->session()->has('managed_guild')) {
            $selectedGuildId = (string) (auth()->user()?->selected_guild_id ?? '');

            if ($selectedGuildId !== '') {
                $restoredGuild = [
                    'id' => $selectedGuildId,
                    'name' => 'Server tersimpan sebelumnya',
                    'icon_url' => null,
                    'owner' => false,
                    'bot_joined' => true,
                    'persisted' => true,
                ];

                $request->session()->put('managed_guild', $restoredGuild);

                if (! $request->session()->has('discord_managed_guilds')) {
                    $request->session()->put('discord_managed_guilds', [$restoredGuild]);
                }
            } else {
                return redirect()->route('guilds.select');
            }
        }

        $hasGamesTable = Schema::hasTable('roblox_games');
        $hasWebhooksTable = Schema::hasTable('discord_webhooks');
        $hasAlertsTable = Schema::hasTable('platform_alerts');
        $hasReportsTable = Schema::hasTable('player_reports');
        $hasRacesTable = Schema::hasTable('race_events');

        $stats = [
            [
                'label' => 'Tracked games',
                'value' => $hasGamesTable ? RobloxGame::count() : null,
                'hint' => 'Universe dan place yang terhubung ke workspace tim.',
            ],
            [
                'label' => 'Active webhooks',
                'value' => $hasWebhooksTable ? DiscordWebhook::query()->where('is_active', true)->count() : null,
                'hint' => 'Webhook Discord yang siap kirim alert atau deploy log.',
            ],
            [
                'label' => 'Open alerts',
                'value' => $hasAlertsTable ? PlatformAlert::query()->whereIn('status', ['open', 'investigating'])->count() : null,
                'hint' => 'Insiden operasional yang masih perlu tindakan.',
            ],
            [
                'label' => 'Pending reports',
                'value' => $hasReportsTable ? PlayerReport::query()->whereIn('status', ['new', 'triaged'])->count() : null,
                'hint' => 'Laporan player atau bug yang belum selesai.',
            ],
            [
                'label' => 'Race events',
                'value' => $hasRacesTable ? RaceEvent::query()->where('status', 'registration_open')->count() : null,
                'hint' => 'Event balap komunitas yang sedang buka registrasi.',
            ],
        ];

        $games = $hasGamesTable
            ? RobloxGame::query()->latest('updated_at')->take(3)->get()
            : collect();

        $alerts = $hasAlertsTable
            ? PlatformAlert::query()->latest('occurred_at')->take(4)->get()
            : collect();

        $reports = $hasReportsTable
            ? PlayerReport::query()->latest()->take(4)->get()
            : collect();

        $webhooks = $hasWebhooksTable
            ? DiscordWebhook::query()->latest('updated_at')->take(3)->get()
            : collect();

        $races = $hasRacesTable
            ? RaceEvent::query()->withCount('participants')->latest()->take(3)->get()
            : collect();

        $managedGuild = $request->session()->get('managed_guild');
        $walletSummary = $walletService->summarizeForGuild(
            (string) ($managedGuild['id'] ?? ''),
            (string) ($managedGuild['name'] ?? ''),
        );

        return view('dashboard', [
            'stats' => $stats,
            'games' => $games,
            'alerts' => $alerts,
            'reports' => $reports,
            'webhooks' => $webhooks,
            'races' => $races,
            'walletSummary' => $walletSummary,
            'hasBotTables' => $hasGamesTable || $hasWebhooksTable || $hasAlertsTable || $hasReportsTable || $hasRacesTable,
            'managedGuild' => $managedGuild,
        ]);
    }
}
