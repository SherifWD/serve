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
        Schema::create('order_item_histories', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_item_id');
        $table->string('action');
        $table->json('snapshot_before')->nullable();
        $table->json('snapshot_after')->nullable();
        $table->string('note')->nullable();
        $table->unsignedBigInteger('user_id')->nullable(); // <-- MUST be nullable!
        $table->timestamps();

        $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_histories');
    }
};
