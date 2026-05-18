<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('key', 'currency')
                ->where('value', 'EGP')
                ->update(['value' => 'USD']);

            DB::table('settings')
                ->where('key', 'loyalty_rule')
                ->where('value', 'like', '%EGP%')
                ->update(['value' => DB::raw("REPLACE(value, 'EGP', 'USD')")]);
        }

        $this->replaceCurrency('fiscal_profiles', 'currency_code', 'EGP', 'USD');
        $this->replaceCurrency('subscription_plans', 'currency', 'EGP', 'USD');
        $this->replaceCurrency('billing_invoices', 'currency', 'EGP', 'USD');
        $this->replaceCurrency('payment_attempts', 'currency', 'EGP', 'USD');
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('key', 'currency')
                ->where('value', 'USD')
                ->update(['value' => 'EGP']);

            DB::table('settings')
                ->where('key', 'loyalty_rule')
                ->where('value', 'like', '%USD%')
                ->update(['value' => DB::raw("REPLACE(value, 'USD', 'EGP')")]);
        }

        $this->replaceCurrency('fiscal_profiles', 'currency_code', 'USD', 'EGP');
        $this->replaceCurrency('subscription_plans', 'currency', 'USD', 'EGP');
        $this->replaceCurrency('billing_invoices', 'currency', 'USD', 'EGP');
        $this->replaceCurrency('payment_attempts', 'currency', 'USD', 'EGP');
    }

    private function replaceCurrency(string $table, string $column, string $from, string $to): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->where($column, $from)
            ->update([$column => $to]);
    }
};
