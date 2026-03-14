<?php

namespace App\Console\Commands;

use App\Services\Discord\DiscordBotInviteUrlFactory;
use Illuminate\Console\Command;

class DiscordInviteUrlCommand extends Command
{
    protected $signature = 'discord:invite-url
        {--permissions= : Permission integer for the bot invite URL}
        {--guild-id= : Prefill a guild/server id}
        {--lock-guild : Disable guild selection in the invite screen}';

    protected $description = 'Generate the Discord bot invite URL';

    public function handle(DiscordBotInviteUrlFactory $factory): int
    {
        $applicationId = config('services.discord.application_id');

        if (! is_string($applicationId) || $applicationId === '') {
            $this->error('DISCORD_APPLICATION_ID belum diisi.');

            return self::FAILURE;
        }

        $url = $factory->make(
            $this->option('guild-id'),
            (bool) $this->option('lock-guild'),
            $this->option('permissions'),
        );

        $this->info('Invite URL:');
        $this->line((string) $url);

        return self::SUCCESS;
    }
}
