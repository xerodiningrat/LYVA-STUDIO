<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipTitlePayment extends Model
{
    protected $fillable = [
        'vip_title_claim_id',
        'map_key',
        'guild_id',
        'guild_name',
        'merchant_order_id',
        'duitku_reference',
        'amount',
        'admin_fee_amount',
        'seller_net_amount',
        'status',
        'payment_url',
        'payment_method',
        'expires_at',
        'paid_at',
        'frozen_until',
        'buyer_email',
        'buyer_discord_user_id',
        'callback_payload',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'frozen_until' => 'datetime',
            'callback_payload' => 'array',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(VipTitleClaim::class, 'vip_title_claim_id');
    }
}
