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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
    $table->foreignId('order_id')->constrained('orders');
    $table->foreignId('driver_id')->constrained('users');
    $table->enum('status', ['assigned', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled']);
    $table->dateTime('pickup_time')->nullable();
    $table->dateTime('delivered_time')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
