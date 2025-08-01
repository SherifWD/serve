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
        Schema::create('financial_summaries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
    $table->date('summary_date');
    $table->decimal('sales', 10, 2)->default(0);
    $table->decimal('expenses', 10, 2)->default(0);
    $table->decimal('net_profit', 10, 2)->virtualAs('sales - expenses');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_summaries');
    }
};
