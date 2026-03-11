<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceParticipant extends Model
{
    protected $fillable = [
        'race_event_id',
        'discord_user_id',
        'discord_username',
        'roblox_username',
        'status',
        'lane',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lane' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'race_event_id');
    }
}
