<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesEvent extends Model
{
    protected $fillable = [
        'roblox_game_id',
        'product_name',
        'product_type',
        'product_id',
        'buyer_name',
        'amount_robux',
        'quantity',
        'purchased_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_robux' => 'integer',
            'quantity' => 'integer',
            'purchased_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(RobloxGame::class, 'roblox_game_id');
    }
}
