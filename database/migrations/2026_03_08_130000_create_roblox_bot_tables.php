<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roblox_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('universe_id')->unique();
            $table->string('place_id')->unique();
            $table->string('group_id')->nullable();
            $table->string('status')->default('healthy');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('discord_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roblox_game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('channel_name');
            $table->text('webhook_url');
            $table->json('event_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roblox_game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('status');
            $table->string('severity')->default('info');
            $table->string('source')->default('system');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('status')->default('open');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('player_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roblox_game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reporter_name');
            $table->string('reported_player_name');
            $table->string('category')->default('bug');
            $table->text('summary');
            $table->string('status')->default('new');
            $table->string('priority')->default('medium');
            $table->string('discord_thread_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_reports');
        Schema::dropIfExists('platform_alerts');
        Schema::dropIfExists('discord_webhooks');
        Schema::dropIfExists('roblox_games');
    }
};
