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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
    $table->foreignId('ingredient_id')->constrained('ingredients');
    $table->enum('type', ['use', 'restock', 'return', 'waste', 'comp']);
    $table->decimal('quantity', 10, 2);
    $table->foreignId('order_item_id')->nullable()->constrained('order_items');
    $table->text('reason')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
