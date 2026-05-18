<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('national_id', 30)->nullable()->after('phone_verified_at');
        });

        Schema::create('customer_otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('destination', 255);
            $table->string('purpose', 50)->default('login');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['destination', 'purpose', 'consumed_at']);
            $table->index(['customer_id', 'purpose', 'expires_at']);
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('billing_period', 20)->default('monthly');
            $table->string('currency', 3)->default('USD');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('max_branches')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_devices')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('restaurant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('trialing');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('next_invoice_at')->nullable();
            $table->timestamp('cancel_at')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('external_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status', 30)->default('open');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('line_items')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('eta_receipt_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('queued');
            $table->string('eta_submission_uuid', 100)->nullable();
            $table->string('eta_request_id', 100)->nullable();
            $table->json('eta_response')->nullable();
            $table->json('payload');
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id', 'status']);
            $table->index(['receipt_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eta_receipt_submissions');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('restaurant_subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('customer_otp_codes');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'phone_verified_at',
                'email_verified_at',
                'national_id',
            ]);
        });
    }
};
