<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RulesAcknowledgement extends Model
{
    protected $fillable = [
        'guild_id',
        'channel_id',
        'message_id',
        'discord_user_id',
        'discord_username',
    ];
}
