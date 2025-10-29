<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('active');
            $table->json('address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('wms_storage_bins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('wms_warehouses')->cascadeOnDelete();
            $table->string('code');
            $table->string('zone')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'warehouse_id', 'code'], 'wms_bin_unique');
        });

        Schema::create('wms_inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('wms_warehouses')->cascadeOnDelete();
            $table->foreignId('storage_bin_id')->constrained('wms_storage_bins')->cascadeOnDelete();
            $table->string('lot_number')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('uom')->default('EA');
            $table->date('received_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'item_id', 'lot_number', 'storage_bin_id'], 'wms_lot_unique');
        });

        Schema::create('wms_transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->foreignId('source_bin_id')->constrained('wms_storage_bins')->cascadeOnDelete();
            $table->foreignId('destination_bin_id')->constrained('wms_storage_bins')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('status')->default('draft'); // draft, picking, picked, in_transit, completed, cancelled
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_transfer_orders');
        Schema::dropIfExists('wms_inventory_lots');
        Schema::dropIfExists('wms_storage_bins');
        Schema::dropIfExists('wms_warehouses');
    }
};
