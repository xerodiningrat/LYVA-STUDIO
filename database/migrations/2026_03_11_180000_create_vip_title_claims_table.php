<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_title_claims', function (Blueprint $table) {
            $table->id();
            $table->string('map_key')->index();
            $table->unsignedBigInteger('gamepass_id')->nullable();
            $table->unsignedBigInteger('roblox_user_id')->index();
            $table->string('roblox_username');
            $table->string('requested_title', 28);
            $table->string('discord_user_id')->nullable()->index();
            $table->string('discord_tag')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('consumed_at')->nullable();
            $table->string('consumed_place_id')->nullable();
            $table->string('consumed_universe_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['map_key', 'roblox_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_title_claims');
    }
};
