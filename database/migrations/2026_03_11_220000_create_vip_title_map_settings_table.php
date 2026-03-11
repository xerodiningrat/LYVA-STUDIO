<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_title_map_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('map_key')->unique();
            $table->unsignedBigInteger('gamepass_id')->default(0);
            $table->string('api_key')->unique();
            $table->unsignedTinyInteger('title_slot')->default(10);
            $table->json('place_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_title_map_settings');
    }
};
