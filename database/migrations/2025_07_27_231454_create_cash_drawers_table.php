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
        Schema::create('cash_drawers', function (Blueprint $table) {
            $table->id();
    $table->foreignId('shift_id')->constrained('shifts');
    $table->foreignId('opened_by')->constrained('users');
    $table->foreignId('closed_by')->nullable()->constrained('users');
    $table->dateTime('open_time');
    $table->dateTime('close_time')->nullable();
    $table->decimal('cash_open_amount', 10, 2);
    $table->decimal('cash_close_amount', 10, 2)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_drawers');
    }
};
