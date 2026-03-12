<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VipTitleMapSetting extends Model
{
    protected $fillable = [
        'name',
        'map_key',
        'gamepass_id',
        'api_key',
        'title_slot',
        'place_ids',
        'script_access_role_ids',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gamepass_id' => 'integer',
            'title_slot' => 'integer',
            'place_ids' => 'array',
            'script_access_role_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
