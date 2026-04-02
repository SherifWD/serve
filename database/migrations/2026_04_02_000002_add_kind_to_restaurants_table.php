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
            $table->string('kind')->default('restaurant')->after('name');
        });

        DB::table('restaurants')
            ->where('name', 'like', '%Cafe%')
            ->orWhere('name', 'like', '%Mocha%')
            ->orWhere('name', 'like', '%Roast%')
            ->update(['kind' => 'cafe']);
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
