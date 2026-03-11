<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VipTitleClaim extends Model
{
    protected $fillable = [
        'map_key',
        'gamepass_id',
        'roblox_user_id',
        'roblox_username',
        'requested_title',
        'discord_user_id',
        'discord_tag',
        'status',
        'requested_at',
        'consumed_at',
        'consumed_place_id',
        'consumed_universe_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'consumed_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
