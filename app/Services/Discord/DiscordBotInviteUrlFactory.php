<?php

namespace App\Services\Discord;

class DiscordBotInviteUrlFactory
{
    public function make(?string $guildId = null, bool $lockGuild = false, ?string $permissions = null): ?string
    {
        $applicationId = (string) config('services.discord.application_id');

        if ($applicationId === '') {
            return null;
        }

        $query = [
            'client_id' => $applicationId,
            'scope' => 'bot applications.commands',
            'permissions' => $permissions ?: (string) config('services.discord.bot_invite_permissions', '274878221376'),
            'integration_type' => 0,
        ];

        if (is_string($guildId) && $guildId !== '') {
            $query['guild_id'] = $guildId;
        }

        if ($lockGuild && isset($query['guild_id'])) {
            $query['disable_guild_select'] = 'true';
        }

        return 'https://discord.com/oauth2/authorize?'.http_build_query($query);
    }
}
