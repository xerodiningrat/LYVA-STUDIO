<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->unsignedInteger('max_players')->default(8);
            $table->unsignedInteger('entry_fee_robux')->default(0);
            $table->string('status')->default('registration_open');
            $table->string('created_by_discord_id')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('race_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_event_id')->constrained()->cascadeOnDelete();
            $table->string('discord_user_id');
            $table->string('discord_username');
            $table->string('roblox_username');
            $table->string('status')->default('registered');
            $table->unsignedInteger('lane')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['race_event_id', 'discord_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_participants');
        Schema::dropIfExists('race_events');
    }
};
