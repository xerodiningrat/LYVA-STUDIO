<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscordVerification extends Model
{
    protected $fillable = [
        'guild_id',
        'discord_user_id',
        'discord_username',
        'roblox_user_id',
        'roblox_username',
        'roblox_display_name',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }
}
