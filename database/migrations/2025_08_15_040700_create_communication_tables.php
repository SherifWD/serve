<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('priority')->default('normal'); // low, normal, high, critical
            $table->string('status')->default('draft'); // draft, scheduled, published, expired
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('audiences')->nullable(); // e.g. departments, roles
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('communication_workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('request_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, approved, rejected, completed, cancelled
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('requested_at')->nullable();
            $table->date('due_at')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('communication_workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_request_id')->constrained('communication_workflow_requests')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // approve, reject, comment, reassign, escalate
            $table->string('status')->default('recorded'); // recorded, pending, completed
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_workflow_actions');
        Schema::dropIfExists('communication_workflow_requests');
        Schema::dropIfExists('communication_announcements');
    }
};
