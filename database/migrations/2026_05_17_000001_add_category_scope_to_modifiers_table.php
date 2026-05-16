<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            if (!Schema::hasColumn('modifiers', 'category_id')) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->constrained('categories')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('modifiers', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            if (Schema::hasColumn('modifiers', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }

            if (Schema::hasColumn('modifiers', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
