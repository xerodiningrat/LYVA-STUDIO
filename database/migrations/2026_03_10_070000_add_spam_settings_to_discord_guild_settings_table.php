<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discord_guild_settings', function (Blueprint $table) {
            $table->boolean('spam_enabled')->default(false)->after('ticket_log_channel_id');
            $table->string('spam_announcement_channel_id')->nullable()->after('spam_enabled');
            $table->string('spam_log_channel_id')->nullable()->after('spam_announcement_channel_id');
            $table->unsignedSmallInteger('spam_threshold')->default(3)->after('spam_log_channel_id');
            $table->unsignedSmallInteger('spam_window_seconds')->default(45)->after('spam_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('discord_guild_settings', function (Blueprint $table) {
            $table->dropColumn([
                'spam_enabled',
                'spam_announcement_channel_id',
                'spam_log_channel_id',
                'spam_threshold',
                'spam_window_seconds',
            ]);
        });
    }
};
