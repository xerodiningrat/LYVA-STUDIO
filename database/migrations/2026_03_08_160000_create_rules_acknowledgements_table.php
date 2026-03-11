<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rules_acknowledgements', function (Blueprint $table): void {
            $table->id();
            $table->string('guild_id')->nullable()->index();
            $table->string('channel_id')->index();
            $table->string('message_id')->index();
            $table->string('discord_user_id');
            $table->string('discord_username');
            $table->timestamps();

            $table->unique(['message_id', 'discord_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rules_acknowledgements');
    }
};
