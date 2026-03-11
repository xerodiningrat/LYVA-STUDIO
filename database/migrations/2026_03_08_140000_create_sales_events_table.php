<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roblox_game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_type');
            $table->string('product_id')->nullable();
            $table->string('buyer_name');
            $table->unsignedInteger('amount_robux')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('purchased_at')->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_events');
    }
};
