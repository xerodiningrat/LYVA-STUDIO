<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscordGuildSetting extends Model
{
    protected $fillable = [
        'guild_id',
        'verification_channel_id',
        'verification_message_id',
        'verification_role_id',
        'ticket_panel_channel_id',
        'ticket_panel_message_id',
        'ticket_support_role_id',
        'ticket_category_id',
        'ticket_log_channel_id',
        'spam_enabled',
        'spam_announcement_channel_id',
        'spam_log_channel_id',
        'spam_threshold',
        'spam_window_seconds',
    ];

    protected $casts = [
        'spam_enabled' => 'boolean',
        'spam_threshold' => 'integer',
        'spam_window_seconds' => 'integer',
    ];
}
