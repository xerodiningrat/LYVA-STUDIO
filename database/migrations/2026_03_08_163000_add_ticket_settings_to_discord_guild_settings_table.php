<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discord_guild_settings', function (Blueprint $table): void {
            $table->string('ticket_panel_channel_id')->nullable()->after('verification_role_id');
            $table->string('ticket_panel_message_id')->nullable()->after('ticket_panel_channel_id');
            $table->string('ticket_support_role_id')->nullable()->after('ticket_panel_message_id');
            $table->string('ticket_category_id')->nullable()->after('ticket_support_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('discord_guild_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'ticket_panel_channel_id',
                'ticket_panel_message_id',
                'ticket_support_role_id',
                'ticket_category_id',
            ]);
        });
    }
};
