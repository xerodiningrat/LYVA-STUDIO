<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAlert extends Model
{
    protected $fillable = [
        'roblox_game_id',
        'type',
        'severity',
        'source',
        'title',
        'message',
        'status',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(RobloxGame::class, 'roblox_game_id');
    }
}
