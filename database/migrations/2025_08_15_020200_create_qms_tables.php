<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_inspection_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('inspection_type')->default('incoming'); // incoming, in_process, final
            $table->json('checklist')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('qms_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_plan_id')->nullable()->constrained('qms_inspection_plans')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, passed, failed
            $table->json('results')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('qms_non_conformities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained('qms_inspections')->nullOnDelete();
            $table->string('code');
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('qms_capa_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('non_conformity_id')->constrained('qms_non_conformities')->cascadeOnDelete();
            $table->string('action_type')->default('corrective'); // corrective, preventive
            $table->text('description');
            $table->string('status')->default('open'); // open, in_progress, verified, closed
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('qms_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('audit_type')->default('internal'); // internal, external, certification
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->json('findings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_audits');
        Schema::dropIfExists('qms_capa_actions');
        Schema::dropIfExists('qms_non_conformities');
        Schema::dropIfExists('qms_inspections');
        Schema::dropIfExists('qms_inspection_plans');
    }
};
