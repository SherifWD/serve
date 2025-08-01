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
        Schema::create('stock_transfers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('from_branch_id')->constrained('branches')->onDelete('cascade');
    $table->foreignId('to_branch_id')->constrained('branches')->onDelete('cascade');
    $table->string('reference_code')->unique();
    $table->decimal('total_quantity', 10, 2);
    $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
