<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('status')->default('active'); // active, suspended, blacklisted
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('procurement_tenders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('title');
            $table->string('status')->default('draft'); // draft, open, closed, awarded, cancelled
            $table->date('opening_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference']);
        });

        Schema::create('procurement_tender_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_id')->constrained('procurement_tenders')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('procurement_vendors')->nullOnDelete();
            $table->date('response_date')->nullable();
            $table->string('status')->default('submitted'); // submitted, shortlisted, awarded, rejected
            $table->decimal('amount', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('procurement_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('requester_name');
            $table->string('department')->nullable();
            $table->string('status')->default('draft'); // draft, pending_approval, approved, rejected, fulfilled
            $table->date('needed_by')->nullable();
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->text('justification')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_purchase_requests');
        Schema::dropIfExists('procurement_tender_responses');
        Schema::dropIfExists('procurement_tenders');
        Schema::dropIfExists('procurement_vendors');
    }
};
