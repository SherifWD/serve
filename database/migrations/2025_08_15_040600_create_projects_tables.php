<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('draft'); // draft, active, on_hold, completed, archived
            $table->string('stage')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('budget_amount', 16, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code'], 'projects_project_unique');
        });

        Schema::create('projects_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects_projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('not_started'); // not_started, in_progress, completed, blocked
            $table->string('priority')->default('medium'); // low, medium, high
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->foreignId('depends_on_task_id')->nullable()->constrained('projects_tasks')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('projects_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects_projects')->cascadeOnDelete();
            $table->string('reference');
            $table->string('title');
            $table->string('change_type')->nullable(); // scope, design, schedule, cost, quality
            $table->string('status')->default('draft'); // draft, submitted, in_review, approved, rejected, implemented
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('requested_at')->nullable();
            $table->date('target_date')->nullable();
            $table->string('risk_level')->nullable(); // low, medium, high
            $table->text('impact_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference'], 'projects_change_request_unique');
        });

        Schema::create('projects_change_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('change_request_id')->constrained('projects_change_requests')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('role')->nullable(); // CAB, Engineering Lead, QA, etc.
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_change_approvals');
        Schema::dropIfExists('projects_change_requests');
        Schema::dropIfExists('projects_tasks');
        Schema::dropIfExists('projects_projects');
    }
};
