<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('category')->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['module_id', 'key'], 'module_feature_unique');
        });

        Schema::create('tenant_module_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_feature_id')->constrained('module_features')->cascadeOnDelete();
            $table->string('status')->default('enabled'); // enabled, disabled
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'module_feature_id'], 'tenant_module_feature_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_module_features');
        Schema::dropIfExists('module_features');
    }
};

