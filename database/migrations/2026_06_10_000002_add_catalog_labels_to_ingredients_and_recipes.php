<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            if (! Schema::hasColumn('ingredients', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
        });

        Schema::table('recipes', function (Blueprint $table) {
            if (! Schema::hasColumn('recipes', 'name')) {
                $table->string('name')->nullable()->after('product_id');
            }

            if (! Schema::hasColumn('recipes', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            if (Schema::hasColumn('recipes', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('recipes', 'name')) {
                $table->dropColumn('name');
            }
        });

        Schema::table('ingredients', function (Blueprint $table) {
            if (Schema::hasColumn('ingredients', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
