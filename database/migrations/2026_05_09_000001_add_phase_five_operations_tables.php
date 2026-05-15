<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_mutations', function (Blueprint $table) {
            $table->id();
            $table->string('client_mutation_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('request_hash', 64);
            $table->string('status', 30)->default('processing');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('payment_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('provider', 100);
            $table->string('display_name');
            $table->string('mode', 30)->default('manual');
            $table->boolean('is_active')->default(true);
            $table->json('credentials')->nullable();
            $table->json('terminal_config')->nullable();
            $table->json('supported_methods')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id', 'provider']);
            $table->index(['restaurant_id', 'is_active']);
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 100)->default('manual');
            $table->string('method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('status', 30)->default('requires_action');
            $table->string('provider_reference')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('client_mutation_id')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['restaurant_id', 'status']);
            $table->index(['client_mutation_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_attempt_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->string('provider', 100)->nullable()->after('method');
            $table->string('provider_reference')->nullable()->after('provider');
            $table->string('client_mutation_id')->nullable()->after('provider_reference');
            $table->index('client_mutation_id');
        });

        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50)->default('receipt');
            $table->string('status', 30)->default('queued');
            $table->unsignedSmallInteger('priority')->default(5);
            $table->json('payload');
            $table->string('printer_profile', 100)->nullable();
            $table->string('printer_endpoint')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'priority']);
            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_attempt_id');
            $table->dropConstrainedForeignId('device_id');
            $table->dropIndex(['client_mutation_id']);
            $table->dropColumn([
                'provider',
                'provider_reference',
                'client_mutation_id',
            ]);
        });

        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payment_provider_configs');
        Schema::dropIfExists('client_mutations');
    }
};
