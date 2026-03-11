<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiscordInviteUrlCommand extends Command
{
    protected $signature = 'discord:invite-url {--permissions=274878221376 : Permission integer for the bot invite URL}';

    protected $description = 'Generate the Discord bot invite URL';

    public function handle(): int
    {
        $applicationId = config('services.discord.application_id');
        $permissions = (string) $this->option('permissions');

        if (! is_string($applicationId) || $applicationId === '') {
            $this->error('DISCORD_APPLICATION_ID belum diisi.');

            return self::FAILURE;
        }

        $url = sprintf(
            'https://discord.com/oauth2/authorize?client_id=%s&scope=%s&permissions=%s',
            $applicationId,
            rawurlencode('bot applications.commands'),
            $permissions,
        );

        $this->info('Invite URL:');
        $this->line($url);

        return self::SUCCESS;
    }
}
