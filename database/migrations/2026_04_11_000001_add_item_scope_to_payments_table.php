<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'item_ids')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->json('item_ids')->nullable();
            });
        }

        if (!Schema::hasColumn('payments', 'scope')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('scope')->default('order');
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'item_ids')) {
                $table->dropColumn('item_ids');
            }

            if (Schema::hasColumn('payments', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
