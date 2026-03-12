<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipTitleWithdrawal extends Model
{
    protected $fillable = [
        'guild_id',
        'guild_name',
        'user_id',
        'requester_discord_user_id',
        'requester_name',
        'gross_amount',
        'withdrawal_fee_amount',
        'net_amount',
        'status',
        'requested_at',
        'ready_at',
        'completed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
