<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurants', 'currency_code')) {
                $table->string('currency_code', 3)->default('USD')->after('kind');
            }
        });

        if (Schema::hasTable('fiscal_profiles')) {
            DB::table('fiscal_profiles')
                ->whereNull('branch_id')
                ->whereNotNull('currency_code')
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->get(['restaurant_id', 'currency_code'])
                ->unique('restaurant_id')
                ->each(function ($profile) {
                    DB::table('restaurants')
                        ->where('id', $profile->restaurant_id)
                        ->update(['currency_code' => strtoupper((string) $profile->currency_code)]);
                });
        }

        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'opens_at')) {
                $table->time('opens_at')->nullable()->after('location');
            }

            if (! Schema::hasColumn('branches', 'closes_at')) {
                $table->time('closes_at')->nullable()->after('opens_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'closes_at')) {
                $table->dropColumn('closes_at');
            }

            if (Schema::hasColumn('branches', 'opens_at')) {
                $table->dropColumn('opens_at');
            }
        });

        Schema::table('restaurants', function (Blueprint $table) {
            if (Schema::hasColumn('restaurants', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
