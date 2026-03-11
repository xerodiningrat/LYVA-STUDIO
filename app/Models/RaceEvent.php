<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaceEvent extends Model
{
    protected $fillable = [
        'title',
        'notes',
        'max_players',
        'entry_fee_robux',
        'status',
        'created_by_discord_id',
        'created_by_name',
        'registration_closes_at',
        'starts_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'max_players' => 'integer',
            'entry_fee_robux' => 'integer',
            'registration_closes_at' => 'datetime',
            'starts_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(RaceParticipant::class);
    }
}
