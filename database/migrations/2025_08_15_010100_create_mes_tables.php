<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mes_production_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('erp_sites')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('active');
            $table->json('layout')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('mes_work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->constrained('mes_production_lines')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('mes_machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained('mes_work_centers')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('status')->default('idle'); // idle, running, maintenance, down
            $table->json('specs')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('mes_work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('erp_items')->nullOnDelete();
            $table->foreignId('production_line_id')->nullable()->constrained('mes_production_lines')->nullOnDelete();
            $table->string('code');
            $table->string('status')->default('planned'); // planned, released, in_progress, completed, closed
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('quantity_completed', 12, 4)->default(0);
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('planned_end_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('mes_production_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained('mes_work_orders')->cascadeOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained('mes_machines')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type');
            $table->timestamp('event_timestamp');
            $table->json('payload')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['tenant_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mes_production_events');
        Schema::dropIfExists('mes_work_orders');
        Schema::dropIfExists('mes_machines');
        Schema::dropIfExists('mes_work_centers');
        Schema::dropIfExists('mes_production_lines');
    }
};
