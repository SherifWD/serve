<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plm_product_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('version')->default('1.0');
            $table->string('lifecycle_state')->default('in_design'); // in_design, prototype, released, retired
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('plm_engineering_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_design_id')->constrained('plm_product_designs')->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, implemented, rejected
            $table->date('effectivity_date')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('plm_design_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_design_id')->constrained('plm_product_designs')->cascadeOnDelete();
            $table->string('document_type')->default('specification');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('version')->default('1.0');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plm_design_documents');
        Schema::dropIfExists('plm_engineering_changes');
        Schema::dropIfExists('plm_product_designs');
    }
};

