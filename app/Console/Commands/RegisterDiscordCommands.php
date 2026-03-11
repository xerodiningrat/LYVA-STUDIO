<?php

namespace App\Console\Commands;

use App\Services\Discord\DiscordCommandCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RegisterDiscordCommands extends Command
{
    protected $signature = 'discord:register-commands {--guild : Register specifically to a guild instead of global} {--guild-id= : Override guild ID for command registration}';

    protected $description = 'Register slash commands to Discord';

    public function handle(DiscordCommandCatalog $catalog): int
    {
        $applicationId = config('services.discord.application_id');
        $botToken = config('services.discord.bot_token');
        $guildId = $this->option('guild-id') ?: config('services.discord.guild_id');
        $useGuild = (bool) $this->option('guild');

        if (! is_string($applicationId) || $applicationId === '') {
            $this->error('DISCORD_APPLICATION_ID belum diisi.');

            return self::FAILURE;
        }

        if (! is_string($botToken) || $botToken === '') {
            $this->error('DISCORD_BOT_TOKEN belum diisi.');

            return self::FAILURE;
        }

        if ($useGuild && (! is_string($guildId) || $guildId === '')) {
            $this->error('DISCORD_GUILD_ID belum diisi. Isi env atau gunakan --guild-id=');

            return self::FAILURE;
        }

        $endpoint = $useGuild
            ? "https://discord.com/api/v10/applications/{$applicationId}/guilds/{$guildId}/commands"
            : "https://discord.com/api/v10/applications/{$applicationId}/commands";

        $response = Http::withToken($botToken, 'Bot')
            ->acceptJson()
            ->put($endpoint, $catalog->definitions());

        if ($response->failed()) {
            $this->error('Gagal register slash commands.');
            $this->line($response->body());

            return self::FAILURE;
        }

        $registered = count($response->json() ?? []);

        $this->info("Berhasil register {$registered} command Discord.");
        $this->line('Mode: '.($useGuild ? "guild {$guildId}" : 'global'));

        return self::SUCCESS;
    }
}
