<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bi_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bi_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->json('layout')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bi_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dashboard_id')->constrained('bi_dashboards')->cascadeOnDelete();
            $table->foreignId('kpi_id')->nullable()->constrained('bi_kpis')->nullOnDelete();
            $table->string('type')->default('metric'); // metric, chart, table
            $table->json('options')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bi_data_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_id')->constrained('bi_kpis')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('value', 16, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'kpi_id', 'snapshot_date'], 'bi_snapshot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_data_snapshots');
        Schema::dropIfExists('bi_dashboard_widgets');
        Schema::dropIfExists('bi_dashboards');
        Schema::dropIfExists('bi_kpis');
    }
};
