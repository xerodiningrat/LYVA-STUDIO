<?php

namespace App\Services\Discord;

use App\Models\DiscordWebhook;
use App\Models\PlatformAlert;
use App\Models\PlayerReport;
use App\Models\RobloxGame;
use App\Models\SalesEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DiscordInteractionService
{
    public function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Signature-Ed25519');
        $timestamp = $request->header('X-Signature-Timestamp');
        $publicKey = config('services.discord.public_key');

        if (! is_string($signature) || ! is_string($timestamp) || ! is_string($publicKey) || $publicKey === '') {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached(
                sodium_hex2bin($signature),
                $timestamp.$request->getContent(),
                sodium_hex2bin($publicKey),
            );
        } catch (\Throwable) {
            return false;
        }
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (($payload['type'] ?? null) === 1) {
            return response()->json(['type' => 1]);
        }

        if (($payload['type'] ?? null) !== 2) {
            return $this->message('Interaction type tidak didukung.', true);
        }

        $command = $payload['data']['name'] ?? '';
        $subcommand = $this->resolveSubcommand($payload['data']['options'] ?? []);
        $options = $this->flattenOptions($payload['data']['options'] ?? []);
        $actor = $payload['member']['user']['username'] ?? $payload['user']['username'] ?? 'unknown-user';

        return match ($command) {
            'ping' => $this->message('Pong. Bot Discord Roblox ops aktif.'),
            'status' => $this->handleStatus(),
            'sales' => $this->handleSales($subcommand),
            'server' => $this->handleServer($subcommand),
            'deploy' => $this->handleDeploy($subcommand, $options),
            'report' => $this->handleReport($subcommand, $options, $actor),
            'script' => $this->handleScript($subcommand),
            'verify' => $this->handleVerify($subcommand, $actor),
            'webhook' => $this->handleWebhook($subcommand),
            default => $this->message('Command belum dikenali.', true),
        };
    }

    private function handleStatus(): JsonResponse
    {
        $hasBotTables = $this->hasBotTables();

        $content = implode("\n", [
            'Status dashboard Roblox ops:',
            '- tracked games: '.($hasBotTables ? RobloxGame::count() : 0),
            '- active webhooks: '.($hasBotTables ? DiscordWebhook::query()->where('is_active', true)->count() : 0),
            '- open alerts: '.($hasBotTables ? PlatformAlert::query()->whereIn('status', ['open', 'investigating'])->count() : 0),
            '- pending reports: '.($hasBotTables ? PlayerReport::query()->whereIn('status', ['new', 'triaged'])->count() : 0),
        ]);

        if (! $hasBotTables) {
            $content .= "\nTabel bot belum siap. Jalankan php artisan migrate.";
        }

        return $this->message($content, true);
    }

    private function handleSales(?string $subcommand): JsonResponse
    {
        return match ($subcommand) {
            'summary' => $this->salesSummary(),
            'live' => $this->salesLive(),
            default => $this->message('Pilih subcommand sales yang valid.', true),
        };
    }

    private function handleServer(?string $subcommand): JsonResponse
    {
        return match ($subcommand) {
            'health' => $this->message("Server health:\n- status: monitoring\n- deploy pipeline: ready\n- webhook listener: ready\n- Roblox runtime: belum dipoll langsung", true),
            'shutdowns' => $this->message("Belum ada feed shutdown real-time.\nGunakan command ini setelah event server shutdown sudah ditarik ke platform_alerts.", true),
            default => $this->message('Pilih subcommand server yang valid.', true),
        };
    }

    /**
     * @param  array<string, string>  $options
     */
    private function handleDeploy(?string $subcommand, array $options): JsonResponse
    {
        return match ($subcommand) {
            'log' => $this->message("Deploy log terbaru belum tersambung ke Roblox publish pipeline.\nGunakan ini nanti untuk place update, badge publish, dan changelog.", true),
            'announce' => $this->message('Deploy note diterima: '.($options['message'] ?? 'tanpa catatan')."\nSaat ini baru echo response untuk tes interaction.", false),
            default => $this->message('Pilih subcommand deploy yang valid.', true),
        };
    }

    /**
     * @param  array<string, string>  $options
     */
    private function handleReport(?string $subcommand, array $options, string $actor): JsonResponse
    {
        if (! $this->hasBotTables()) {
            return $this->message('Tabel report belum ada. Jalankan php artisan migrate dulu.', true);
        }

        return match ($subcommand) {
            'player' => $this->createReport(
                reporter: $actor,
                reported: $options['player'] ?? 'unknown-player',
                category: 'player',
                priority: 'high',
                summary: $options['reason'] ?? 'Tanpa alasan'
            ),
            'bug' => $this->createReport(
                reporter: $actor,
                reported: 'game-system',
                category: 'bug',
                priority: $options['severity'] ?? 'medium',
                summary: $options['summary'] ?? 'Tanpa ringkasan'
            ),
            default => $this->message('Pilih subcommand report yang valid.', true),
        };
    }

    private function handleVerify(?string $subcommand, string $actor): JsonResponse
    {
        return match ($subcommand) {
            'start' => $this->message("Verifikasi dimulai untuk `{$actor}`.\nTahap berikutnya: generate kode/link verifikasi Roblox dan simpan mapping akun.", true),
            'check' => $this->message("Status verifikasi `{$actor}` belum tersedia karena modul account linking belum dibuat.", true),
            'unlink' => $this->message("Unlink untuk `{$actor}` belum aktif. Nanti command ini akan menghapus mapping Discord dan Roblox.", true),
            default => $this->message('Pilih subcommand verify yang valid.', true),
        };
    }

    private function handleScript(?string $subcommand): JsonResponse
    {
        $routes = [
            'devproduct' => route('roblox.scripts.download', 'devproduct'),
            'gamepass-server' => route('roblox.scripts.download', 'gamepass-server'),
            'gamepass-client' => route('roblox.scripts.download', 'gamepass-client'),
            'catalog' => route('roblox.scripts.download', 'catalog'),
            'remote' => route('roblox.scripts.download', 'remote'),
            'readme' => route('roblox.scripts.download', 'readme'),
        ];

        if (! isset($routes[$subcommand])) {
            return $this->message('Pilih subcommand script yang valid.', true);
        }

        return $this->message("Ambil script Roblox di: {$routes[$subcommand]}", true);
    }

    private function handleWebhook(?string $subcommand): JsonResponse
    {
        return match ($subcommand) {
            'test' => $this->message("Webhook test berhasil.\nInteraction endpoint aktif dan signature Discord lolos verifikasi.", true),
            default => $this->message('Pilih subcommand webhook yang valid.', true),
        };
    }

    private function createReport(
        string $reporter,
        string $reported,
        string $category,
        string $priority,
        string $summary,
    ): JsonResponse {
        $report = PlayerReport::query()->create([
            'reporter_name' => $reporter,
            'reported_player_name' => $reported,
            'category' => $category,
            'summary' => $summary,
            'priority' => $priority,
            'status' => 'new',
            'payload' => [
                'source' => 'discord_slash_command',
            ],
        ]);

        return $this->message("Report #{$report->id} dibuat.\ncategory: {$category}\nsubject: {$reported}\npriority: {$priority}", true);
    }

    private function salesSummary(): JsonResponse
    {
        if (! Schema::hasTable('sales_events')) {
            return $this->message('Tabel sales_events belum ada. Jalankan migration dulu.', true);
        }

        $windowStart = now()->subDay();
        $query = SalesEvent::query()->where('purchased_at', '>=', $windowStart);
        $transactions = (clone $query)->count();
        $robuxTotal = (clone $query)->get()->sum(fn ($item) => $item->amount_robux * $item->quantity);
        $top = (clone $query)->get()
            ->groupBy('product_name')
            ->map(fn ($items) => $items->sum(fn ($item) => $item->amount_robux * $item->quantity))
            ->sortDesc()
            ->keys()
            ->first();

        return $this->message("Sales summary 24h:\n- transactions: {$transactions}\n- total robux: {$robuxTotal}\n- top product: ".($top ?? 'belum ada'), true);
    }

    private function salesLive(): JsonResponse
    {
        if (! Schema::hasTable('sales_events')) {
            return $this->message('Tabel sales_events belum ada. Jalankan migration dulu.', true);
        }

        $events = SalesEvent::query()
            ->latest('purchased_at')
            ->take(5)
            ->get();

        if ($events->isEmpty()) {
            return $this->message('Belum ada sales event yang masuk ke Laravel.', true);
        }

        $content = $events->map(function (SalesEvent $event) {
            return "#{$event->id} {$event->product_name} - {$event->buyer_name} - {$event->amount_robux} R$ x{$event->quantity}";
        })->implode("\n");

        return $this->message($content, true);
    }

    private function hasBotTables(): bool
    {
        return Schema::hasTable('roblox_games')
            && Schema::hasTable('discord_webhooks')
            && Schema::hasTable('platform_alerts')
            && Schema::hasTable('player_reports');
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function resolveSubcommand(array $options): ?string
    {
        foreach ($options as $option) {
            if (($option['type'] ?? null) === 1) {
                return $option['name'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @return array<string, string>
     */
    private function flattenOptions(array $options): array
    {
        $resolved = [];

        foreach ($options as $option) {
            if (($option['type'] ?? null) === 1) {
                foreach ($option['options'] ?? [] as $nested) {
                    if (isset($nested['name'], $nested['value'])) {
                        $resolved[$nested['name']] = (string) $nested['value'];
                    }
                }

                continue;
            }

            if (isset($option['name'], $option['value'])) {
                $resolved[$option['name']] = (string) $option['value'];
            }
        }

        return $resolved;
    }

    private function message(string $content, bool $ephemeral = true): JsonResponse
    {
        return response()->json([
            'type' => 4,
            'data' => [
                'content' => $content,
                'flags' => $ephemeral ? 64 : 0,
            ],
        ]);
    }
}
