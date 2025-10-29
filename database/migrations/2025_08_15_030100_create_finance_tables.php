<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('account_type'); // asset, liability, equity, revenue, expense
            $table->string('parent_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('finance_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->date('entry_date');
            $table->string('description')->nullable();
            $table->string('status')->default('draft'); // draft, posted, reversed
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained('finance_journal_entries')->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained('finance_ledger_accounts')->cascadeOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_accounts_payable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('vendor_name');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('open'); // open, paid, cancelled
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'invoice_number']);
        });

        Schema::create('finance_accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('open'); // open, paid, cancelled
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounts_receivable');
        Schema::dropIfExists('finance_accounts_payable');
        Schema::dropIfExists('finance_journal_entry_lines');
        Schema::dropIfExists('finance_journal_entries');
        Schema::dropIfExists('finance_ledger_accounts');
    }
};
