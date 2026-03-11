<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_verifications', function (Blueprint $table): void {
            $table->id();
            $table->string('guild_id')->nullable()->index();
            $table->string('discord_user_id')->unique();
            $table->string('discord_username');
            $table->string('roblox_user_id')->unique();
            $table->string('roblox_username');
            $table->string('roblox_display_name');
            $table->timestamp('verified_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_verifications');
    }
};
