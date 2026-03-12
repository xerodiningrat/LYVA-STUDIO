<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_title_map_settings', function (Blueprint $table) {
            $table->string('guild_id')->nullable()->after('id')->index();
            $table->string('guild_name')->nullable()->after('guild_id');
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('guild_name')->index();
            $table->string('owner_discord_user_id')->nullable()->after('owner_user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('vip_title_map_settings', function (Blueprint $table) {
            $table->dropColumn([
                'guild_id',
                'guild_name',
                'owner_user_id',
                'owner_discord_user_id',
            ]);
        });
    }
};
