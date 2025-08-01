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
        Schema::create('category_answers', function (Blueprint $table) {
            $table->id();
    $table->foreignId('order_item_id')->constrained()->onDelete('cascade'); // your order_items table
    $table->foreignId('choice_id')->constrained('category_choices')->onDelete('cascade');
    $table->string('image')->nullable();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_answers');
    }
};
