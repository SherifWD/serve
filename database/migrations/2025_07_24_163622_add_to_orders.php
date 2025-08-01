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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
    $table->string('coupon_code')->nullable();
    $table->string('payment_method')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->enum('payment_status', ['unpaid', 'paid', 'partial'])->default('unpaid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
