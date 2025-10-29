<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrms_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('employment_status')->default('active'); // active, probation, terminated, on_leave
            $table->date('hire_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'employee_number']);
        });

        Schema::create('hrms_employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('hrms_workers')->cascadeOnDelete();
            $table->string('contract_type')->default('permanent'); // permanent, temporary, contractor
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hrms_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('hrms_workers')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('status')->default('present'); // present, absent, remote, leave
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'worker_id', 'attendance_date'], 'hrms_attendance_unique');
        });

        Schema::create('hrms_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('period'); // e.g., 2025-08
            $table->string('status')->default('draft'); // draft, processing, paid, closed
            $table->date('pay_date')->nullable();
            $table->decimal('gross_total', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference']);
        });

        Schema::create('hrms_payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('hrms_payroll_runs')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('hrms_workers')->cascadeOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->json('breakdown')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hrms_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('hrms_workers')->cascadeOnDelete();
            $table->string('leave_type'); // vacation, sick, unpaid, training
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hrms_training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('scheduled_date')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hrms_training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_session_id')->constrained('hrms_training_sessions')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('hrms_workers')->cascadeOnDelete();
            $table->string('status')->default('assigned'); // assigned, attended, completed, missed
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'training_session_id', 'worker_id'], 'hrms_training_assign_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrms_training_assignments');
        Schema::dropIfExists('hrms_training_sessions');
        Schema::dropIfExists('hrms_leave_requests');
        Schema::dropIfExists('hrms_payroll_entries');
        Schema::dropIfExists('hrms_payroll_runs');
        Schema::dropIfExists('hrms_attendance_records');
        Schema::dropIfExists('hrms_employment_contracts');
        Schema::dropIfExists('hrms_workers');
    }
};
