<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scm_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('scm_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('scm_suppliers')->cascadeOnDelete();
            $table->string('po_number');
            $table->string('status')->default('draft'); // draft, approved, sent, received, closed, cancelled
            $table->date('order_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'po_number']);
        });

        Schema::create('scm_purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('scm_purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->string('uom')->default('EA');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scm_inbound_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('scm_purchase_orders')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('status')->default('pending'); // pending, received, partial, closed
            $table->date('arrival_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scm_demand_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->string('period'); // e.g., 2025-Q4
            $table->decimal('forecast_quantity', 12, 3)->default(0);
            $table->string('planning_strategy')->nullable();
            $table->json('assumptions')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'item_id', 'period'], 'scm_demand_plan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scm_demand_plans');
        Schema::dropIfExists('scm_inbound_shipments');
        Schema::dropIfExists('scm_purchase_order_lines');
        Schema::dropIfExists('scm_purchase_orders');
        Schema::dropIfExists('scm_suppliers');
    }
};
