<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_title_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vip_title_claim_id')->constrained('vip_title_claims')->cascadeOnDelete();
            $table->string('map_key')->index();
            $table->string('merchant_order_id')->unique();
            $table->string('duitku_reference')->nullable()->index();
            $table->unsignedInteger('amount');
            $table->string('status', 32)->default('pending')->index();
            $table->text('payment_url')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->string('buyer_email')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamps();

            $table->index(['map_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_title_payments');
    }
};
