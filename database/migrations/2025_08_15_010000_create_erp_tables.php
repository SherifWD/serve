<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('active');
            $table->string('timezone')->default('UTC');
            $table->json('address')->nullable();
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('erp_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('erp_sites')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('erp_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('erp_departments')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('erp_item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('erp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('erp_item_categories')->nullOnDelete();
            $table->string('sku')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('type')->default('manufactured'); // manufactured, purchased, service
            $table->string('uom')->default('EA');
            $table->string('status')->default('active');
            $table->decimal('standard_cost', 12, 4)->default(0);
            $table->decimal('list_price', 12, 4)->default(0);
            $table->json('attributes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('erp_bom_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('erp_items')->cascadeOnDelete();
            $table->string('code');
            $table->string('revision')->default('A');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status')->default('draft'); // draft, active, archived
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['tenant_id', 'code', 'revision'], 'erp_bom_unique');
        });

        Schema::create('erp_bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_id')->constrained('erp_bom_headers')->cascadeOnDelete();
            $table->foreignId('component_item_id')->constrained('erp_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->string('uom')->default('EA');
            $table->unsignedInteger('sequence')->default(1);
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['bom_id', 'component_item_id'], 'bom_component_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_bom_lines');
        Schema::dropIfExists('erp_bom_headers');
        Schema::dropIfExists('erp_items');
        Schema::dropIfExists('erp_item_categories');
        Schema::dropIfExists('erp_cost_centers');
        Schema::dropIfExists('erp_departments');
        Schema::dropIfExists('erp_sites');
    }
};
