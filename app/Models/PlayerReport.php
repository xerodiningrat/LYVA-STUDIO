<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerReport extends Model
{
    protected $fillable = [
        'roblox_game_id',
        'reporter_name',
        'reported_player_name',
        'category',
        'summary',
        'status',
        'priority',
        'discord_thread_url',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(RobloxGame::class, 'roblox_game_id');
    }
}
