<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_guild_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('guild_id')->unique();
            $table->string('verification_channel_id')->nullable();
            $table->string('verification_message_id')->nullable();
            $table->string('verification_role_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_guild_settings');
    }
};
