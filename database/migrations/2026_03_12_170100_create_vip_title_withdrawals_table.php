<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_title_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id')->index();
            $table->string('guild_name')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requester_discord_user_id')->nullable()->index();
            $table->string('requester_name')->nullable();
            $table->unsignedInteger('gross_amount');
            $table->unsignedInteger('withdrawal_fee_amount');
            $table->unsignedInteger('net_amount');
            $table->string('status', 32)->default('processing')->index();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('ready_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_title_withdrawals');
    }
};
