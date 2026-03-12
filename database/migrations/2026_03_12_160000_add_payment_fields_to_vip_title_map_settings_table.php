<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_title_map_settings', function (Blueprint $table) {
            $table->string('claim_mode', 24)->default('vip_gamepass')->after('gamepass_id');
            $table->unsignedInteger('title_price_idr')->nullable()->after('title_slot');
            $table->unsignedSmallInteger('payment_expiry_minutes')->default(60)->after('title_price_idr');
            $table->string('button_label', 100)->nullable()->after('payment_expiry_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('vip_title_map_settings', function (Blueprint $table) {
            $table->dropColumn([
                'claim_mode',
                'title_price_idr',
                'payment_expiry_minutes',
                'button_label',
            ]);
        });
    }
};
