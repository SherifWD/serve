<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM('pending','preparing','prepared','paid','cancelled','refunded') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `orders` MODIFY `status` ENUM('pending','preparing','prepared','paid','cancelled') DEFAULT 'pending'");
    }
};

