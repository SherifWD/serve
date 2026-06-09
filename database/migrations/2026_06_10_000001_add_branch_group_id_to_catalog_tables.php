<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'products', 'recipes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'branch_group_id')) {
                    $table->uuid('branch_group_id')->nullable()->after('branch_id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['recipes', 'products', 'categories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'branch_group_id')) {
                    $table->dropColumn('branch_group_id');
                }
            });
        }
    }
};
