<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordWebhook extends Model
{
    protected $fillable = [
        'roblox_game_id',
        'name',
        'channel_name',
        'webhook_url',
        'event_types',
        'is_active',
        'last_delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'event_types' => 'array',
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(RobloxGame::class, 'roblox_game_id');
    }
}
