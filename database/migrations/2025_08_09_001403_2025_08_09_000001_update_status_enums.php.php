<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            if (!Schema::hasColumn('tables', 'status')) {
                Schema::table('tables', function (Blueprint $table) {
                    $table->string('status')->default('open');
                });
            }

            return;
        }

        // MySQL ENUM modify (adjust list if you already have more states)
        DB::statement("
            ALTER TABLE orders
            MODIFY COLUMN status ENUM(
                'pending','open','running','cashier','preparing','prepared','paid','closed'
            ) NOT NULL DEFAULT 'pending'
        ");

        // Ensure tables.status exists and is ENUM (create/alter as needed)
        // If `status` doesn't exist, add it:
        $hasStatus = Schema::hasColumn('tables', 'status');
        if (!$hasStatus) {
            DB::statement("
                ALTER TABLE `tables`
                ADD COLUMN `status` ENUM('open','occupied','cashier') NOT NULL DEFAULT 'open'
                AFTER `updated_at`
            ");
        } else {
            DB::statement("
                ALTER TABLE `tables`
                MODIFY COLUMN `status` ENUM('open','occupied','cashier') NOT NULL DEFAULT 'open'
            ");
        }
    }

    public function down(): void
    {
        // No-op (or restore your original ENUM list)
    }
};
