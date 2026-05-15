<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('recipes')
            ->select(['id', 'product_id'])
            ->whereNull('branch_id')
            ->orderBy('id')
            ->chunkById(100, function ($recipes): void {
                $branchIds = DB::table('products')
                    ->whereIn('id', $recipes->pluck('product_id')->filter()->all())
                    ->pluck('branch_id', 'id');

                foreach ($recipes as $recipe) {
                    $branchId = $branchIds->get($recipe->product_id);

                    if ($branchId) {
                        DB::table('recipes')
                            ->where('id', $recipe->id)
                            ->update(['branch_id' => $branchId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
