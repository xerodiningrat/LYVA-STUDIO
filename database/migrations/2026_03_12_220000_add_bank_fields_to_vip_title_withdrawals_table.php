<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vip_title_withdrawals', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('requester_name');
            $table->string('account_number')->nullable()->after('bank_name');
            $table->string('account_holder_name')->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('vip_title_withdrawals', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'account_number',
                'account_holder_name',
            ]);
        });
    }
};
