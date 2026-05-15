<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->decimal('balance_before', 12, 3)->nullable()->after('quantity');
            $table->decimal('balance_after', 12, 3)->nullable()->after('balance_before');
            $table->string('source_type')->nullable()->after('reason');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('reference_code')->nullable()->after('source_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('reference_code')->nullable()->unique()->after('id');
            $table->json('items')->nullable()->after('order_date');
            $table->timestamp('received_at')->nullable()->after('items');
            $table->text('notes')->nullable()->after('received_at');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->json('items')->nullable()->after('total_quantity');
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->text('notes')->nullable()->after('completed_at');
        });

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->string('operation')->nullable()->after('type');
            $table->decimal('before_quantity', 12, 3)->nullable()->after('quantity');
            $table->decimal('after_quantity', 12, 3)->nullable()->after('before_quantity');
            $table->string('reference_code')->nullable()->after('reason');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('uuid');
            $table->string('printer_profile')->nullable()->after('payment_provider');
            $table->unsignedSmallInteger('printer_paper_width_mm')->nullable()->after('printer_profile');
            $table->string('printer_endpoint')->nullable()->after('printer_paper_width_mm');
            $table->json('capabilities')->nullable()->after('printer_endpoint');
            $table->boolean('is_active')->default(true)->after('capabilities');
            $table->timestamp('last_seen_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_provider',
                'printer_profile',
                'printer_paper_width_mm',
                'printer_endpoint',
                'capabilities',
                'is_active',
                'last_seen_at',
            ]);
        });

        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->dropColumn([
                'operation',
                'before_quantity',
                'after_quantity',
                'reference_code',
            ]);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn(['items', 'completed_at', 'notes']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique(['reference_code']);
            $table->dropColumn(['reference_code', 'items', 'received_at', 'notes']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'balance_before',
                'balance_after',
                'source_type',
                'source_id',
                'reference_code',
            ]);
        });
    }
};
