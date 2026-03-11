<?php

namespace App\Http\Controllers;

use App\Services\Discord\DiscordCommandCatalog;

class DiscordSetupController extends Controller
{
    public function __invoke(DiscordCommandCatalog $catalog)
    {
        $applicationId = config('services.discord.application_id');
        $guildId = config('services.discord.guild_id');
        $publicKey = config('services.discord.public_key');
        $botToken = config('services.discord.bot_token');
        $appUrl = rtrim((string) config('app.url'), '/');

        $inviteUrl = is_string($applicationId) && $applicationId !== ''
            ? sprintf(
                'https://discord.com/oauth2/authorize?client_id=%s&scope=%s&permissions=%s',
                $applicationId,
                rawurlencode('bot applications.commands'),
                '274878221376',
            )
            : null;

        $interactionUrl = $appUrl !== '' ? "{$appUrl}/discord/interactions" : null;

        $setupChecks = [
            [
                'label' => 'Application ID',
                'value' => $applicationId ?: 'Belum diisi',
                'ready' => filled($applicationId),
            ],
            [
                'label' => 'Public key',
                'value' => $publicKey ? substr((string) $publicKey, 0, 10).'...' : 'Belum diisi',
                'ready' => filled($publicKey),
            ],
            [
                'label' => 'Bot token',
                'value' => $botToken ? 'Tersimpan di env' : 'Belum diisi',
                'ready' => filled($botToken),
            ],
            [
                'label' => 'Guild ID',
                'value' => $guildId ?: 'Opsional untuk mode global',
                'ready' => true,
            ],
            [
                'label' => 'APP_URL publik',
                'value' => $appUrl ?: 'Belum diisi',
                'ready' => $appUrl !== '' && ! str_contains($appUrl, 'localhost') && ! str_contains($appUrl, '127.0.0.1'),
            ],
        ];

        $commands = [
            'php artisan discord:invite-url',
            'php artisan discord:register-commands',
            'php artisan discord:register-commands --guild',
            'php artisan config:clear',
            'npm run bot:register',
            'npm run bot:start',
            'npm run dev:all',
        ];

        $features = $this->flattenCommandLabels($catalog->definitions());

        return view('discord.setup', compact(
            'inviteUrl',
            'interactionUrl',
            'setupChecks',
            'commands',
            'features',
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     * @return array<int, string>
     */
    private function flattenCommandLabels(array $definitions): array
    {
        $features = [];

        foreach ($definitions as $definition) {
            $commandName = $definition['name'] ?? null;

            if (! is_string($commandName) || $commandName === '') {
                continue;
            }

            $options = $definition['options'] ?? [];

            if (! is_array($options) || $options === []) {
                $features[] = "/{$commandName}";
                continue;
            }

            $subcommands = array_filter(
                $options,
                fn (mixed $option): bool => is_array($option) && (($option['type'] ?? null) === 1) && is_string($option['name'] ?? null)
            );

            if ($subcommands === []) {
                $features[] = "/{$commandName}";
                continue;
            }

            foreach ($subcommands as $subcommand) {
                $features[] = sprintf('/%s %s', $commandName, $subcommand['name']);
            }
        }

        return $features;
    }
}
