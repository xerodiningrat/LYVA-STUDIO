<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipTitlePayment extends Model
{
    protected $fillable = [
        'vip_title_claim_id',
        'map_key',
        'merchant_order_id',
        'duitku_reference',
        'amount',
        'status',
        'payment_url',
        'payment_method',
        'expires_at',
        'paid_at',
        'buyer_email',
        'callback_payload',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'callback_payload' => 'array',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(VipTitleClaim::class, 'vip_title_claim_id');
    }
}
