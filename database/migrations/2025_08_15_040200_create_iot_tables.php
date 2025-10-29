<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('device_key');
            $table->string('name');
            $table->string('device_type')->nullable();
            $table->string('status')->default('inactive'); // inactive, active, maintenance, offline
            $table->json('location')->nullable();
            $table->json('metadata')->nullable();
            $table->date('installed_at')->nullable();
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'device_key']);
        });

        Schema::create('iot_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('iot_devices')->cascadeOnDelete();
            $table->string('tag');
            $table->string('name');
            $table->string('unit')->nullable();
            $table->string('data_type')->default('float'); // float, integer, boolean, string
            $table->decimal('threshold_min', 14, 4)->nullable();
            $table->decimal('threshold_max', 14, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'tag']);
        });

        Schema::create('iot_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sensor_id')->constrained('iot_sensors')->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->decimal('value', 16, 4)->nullable();
            $table->string('quality')->nullable(); // good, warning, alarm
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['sensor_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_readings');
        Schema::dropIfExists('iot_sensors');
        Schema::dropIfExists('iot_devices');
    }
};
