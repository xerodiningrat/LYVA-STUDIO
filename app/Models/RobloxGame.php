<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RobloxGame extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'universe_id',
        'place_id',
        'group_id',
        'status',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(PlatformAlert::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PlayerReport::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(DiscordWebhook::class);
    }
}
