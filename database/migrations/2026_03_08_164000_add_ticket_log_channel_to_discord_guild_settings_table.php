<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discord_guild_settings', function (Blueprint $table): void {
            $table->string('ticket_log_channel_id')->nullable()->after('ticket_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('discord_guild_settings', function (Blueprint $table): void {
            $table->dropColumn('ticket_log_channel_id');
        });
    }
};
