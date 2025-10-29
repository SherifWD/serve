<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cmms_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('asset_type')->nullable();
            $table->string('status')->default('active'); // active, inactive, retired
            $table->json('location')->nullable();
            $table->json('metadata')->nullable();
            $table->date('commissioned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('cmms_maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('cmms_assets')->cascadeOnDelete();
            $table->string('name');
            $table->string('frequency'); // e.g. monthly, quarterly
            $table->integer('interval_days')->nullable();
            $table->json('tasks')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cmms_work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('cmms_assets')->nullOnDelete();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained('cmms_maintenance_plans')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('open'); // open, in_progress, completed, cancelled
            $table->string('priority')->default('medium'); // low, medium, high
            $table->text('description')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cmms_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained('cmms_work_orders')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('logged_at');
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cmms_spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('uom')->default('EA');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmms_spare_parts');
        Schema::dropIfExists('cmms_maintenance_logs');
        Schema::dropIfExists('cmms_work_orders');
        Schema::dropIfExists('cmms_maintenance_plans');
        Schema::dropIfExists('cmms_assets');
    }
};
