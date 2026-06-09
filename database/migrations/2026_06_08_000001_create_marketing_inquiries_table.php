<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 120);
            $table->string('business_name', 160);
            $table->string('role', 120)->nullable();
            $table->string('email', 160);
            $table->string('phone', 40);
            $table->string('city', 120)->nullable();
            $table->string('website', 180)->nullable();
            $table->string('business_type', 40);
            $table->unsignedSmallInteger('branch_count')->nullable();
            $table->unsignedSmallInteger('staff_count')->nullable();
            $table->string('current_system', 160)->nullable();
            $table->json('order_channels')->nullable();
            $table->json('interest_areas')->nullable();
            $table->json('devices')->nullable();
            $table->string('timeline', 40);
            $table->string('budget_range', 80)->nullable();
            $table->text('pain_points');
            $table->text('success_notes')->nullable();
            $table->string('preferred_contact_method', 40);
            $table->string('best_contact_time', 120)->nullable();
            $table->boolean('consent_to_contact')->default(false);
            $table->string('status', 40)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_inquiries');
    }
};
