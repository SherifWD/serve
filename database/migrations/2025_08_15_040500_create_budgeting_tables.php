<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgeting_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code'], 'budgeting_cost_center_unique');
        });

        Schema::create('budgeting_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('budgeting_cost_centers')->cascadeOnDelete();
            $table->string('fiscal_year');
            $table->string('period'); // e.g. FY25-Q1, 2025-01
            $table->string('status')->default('draft'); // draft, submitted, approved, locked
            $table->decimal('planned_amount', 16, 2);
            $table->decimal('approved_amount', 16, 2)->nullable();
            $table->decimal('forecast_amount', 16, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->json('assumptions')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'cost_center_id', 'period'], 'budgeting_budget_unique');
        });

        Schema::create('budgeting_actuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('budgeting_cost_centers')->cascadeOnDelete();
            $table->string('fiscal_year');
            $table->string('period');
            $table->decimal('actual_amount', 16, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('source_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'cost_center_id', 'period', 'source_reference'], 'budgeting_actual_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgeting_actuals');
        Schema::dropIfExists('budgeting_budgets');
        Schema::dropIfExists('budgeting_cost_centers');
    }
};
