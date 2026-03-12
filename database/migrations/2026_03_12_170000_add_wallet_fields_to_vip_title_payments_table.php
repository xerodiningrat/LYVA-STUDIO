<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_title_payments', function (Blueprint $table) {
            $table->string('guild_id')->nullable()->after('map_key')->index();
            $table->string('guild_name')->nullable()->after('guild_id');
            $table->string('buyer_discord_user_id')->nullable()->after('buyer_email')->index();
            $table->unsignedInteger('admin_fee_amount')->default(0)->after('amount');
            $table->unsignedInteger('seller_net_amount')->default(0)->after('admin_fee_amount');
            $table->timestamp('frozen_until')->nullable()->after('paid_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('vip_title_payments', function (Blueprint $table) {
            $table->dropColumn([
                'guild_id',
                'guild_name',
                'buyer_discord_user_id',
                'admin_fee_amount',
                'seller_net_amount',
                'frozen_until',
            ]);
        });
    }
};
