<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // BUY/SELL
            $table->decimal('crypto_amount', 18, 8);
            $table->string('crypto_currency'); // BTC, ETH, etc.
            $table->decimal('fiat_amount', 12, 2);
            $table->string('fiat_currency')->default('XOF');
            $table->string('payment_method'); // TMONEY, FLOOZ, ORANGE
            $table->string('phone_number');
            $table->string('status'); // PENDING, COMPLETED, FAILED
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
