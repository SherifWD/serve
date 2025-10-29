<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hse_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('title');
            $table->date('incident_date');
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('open'); // open, investigating, resolved, closed
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hse_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->json('findings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hse_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained('hse_incidents')->nullOnDelete();
            $table->foreignId('audit_id')->nullable()->constrained('hse_audits')->nullOnDelete();
            $table->string('action_type')->default('corrective'); // corrective, preventive
            $table->text('description');
            $table->string('status')->default('open'); // open, in_progress, verified, closed
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hse_training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('session_date')->nullable();
            $table->string('trainer')->nullable();
            $table->json('attendees')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hse_training_records');
        Schema::dropIfExists('hse_actions');
        Schema::dropIfExists('hse_audits');
        Schema::dropIfExists('hse_incidents');
    }
};
