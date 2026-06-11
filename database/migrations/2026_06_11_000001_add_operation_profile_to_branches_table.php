<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'operation_mode')) {
                $table->string('operation_mode')->nullable()->after('location');
            }

            if (! Schema::hasColumn('branches', 'operation_label')) {
                $table->string('operation_label')->nullable()->after('operation_mode');
            }

            if (! Schema::hasColumn('branches', 'operation_features')) {
                $table->json('operation_features')->nullable()->after('operation_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'operation_features')) {
                $table->dropColumn('operation_features');
            }

            if (Schema::hasColumn('branches', 'operation_label')) {
                $table->dropColumn('operation_label');
            }

            if (Schema::hasColumn('branches', 'operation_mode')) {
                $table->dropColumn('operation_mode');
            }
        });
    }
};
