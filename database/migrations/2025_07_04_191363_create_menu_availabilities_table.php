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
        Schema::create('menu_availabilities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_id')->constrained()->onDelete('cascade');
    $table->string('day_of_week'); // e.g. Monday
    $table->time('start_time');
    $table->time('end_time');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_availabilities');
    }
};
