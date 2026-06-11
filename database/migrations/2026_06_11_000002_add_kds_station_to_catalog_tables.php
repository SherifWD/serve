<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'kds_station')) {
                $table->string('kds_station', 40)->nullable()->after('branch_id')->index();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'kds_station')) {
                $table->string('kds_station', 40)->nullable()->after('category_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'kds_station')) {
                $table->dropIndex(['kds_station']);
                $table->dropColumn('kds_station');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'kds_station')) {
                $table->dropIndex(['kds_station']);
                $table->dropColumn('kds_station');
            }
        });
    }
};
