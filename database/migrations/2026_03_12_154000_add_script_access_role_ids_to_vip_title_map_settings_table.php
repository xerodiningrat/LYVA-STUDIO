<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_title_map_settings', function (Blueprint $table) {
            $table->json('script_access_role_ids')->nullable()->after('place_ids');
        });
    }

    public function down(): void
    {
        Schema::table('vip_title_map_settings', function (Blueprint $table) {
            $table->dropColumn('script_access_role_ids');
        });
    }
};
