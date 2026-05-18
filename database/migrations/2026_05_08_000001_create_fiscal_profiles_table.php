<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name')->default('Default fiscal profile');
            $table->boolean('is_default')->default(false);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('vat_rate', 6, 4)->default(0.1400);
            $table->boolean('price_includes_vat')->default(true);
            $table->string('vat_tax_type', 30)->default('T1');
            $table->string('vat_subtype', 50)->default('V009');
            $table->decimal('buyer_id_threshold', 12, 2)->default(150000);
            $table->string('default_payment_method_code', 10)->default('C');
            $table->string('eta_receipt_type', 20)->default('SC');
            $table->string('eta_type_version', 20)->default('1.2');
            $table->string('eta_seller_rin', 30)->nullable();
            $table->string('eta_seller_name', 200)->nullable();
            $table->string('eta_branch_code', 50)->nullable();
            $table->string('eta_device_serial_number', 100)->nullable();
            $table->string('eta_activity_code', 10)->nullable();
            $table->string('address_country', 2)->default('EG');
            $table->string('address_governate', 100)->nullable();
            $table->string('address_region_city', 100)->nullable();
            $table->string('address_street', 200)->nullable();
            $table->string('address_building_number', 100)->nullable();
            $table->string('address_postal_code', 30)->nullable();
            $table->string('address_floor', 100)->nullable();
            $table->string('address_room', 100)->nullable();
            $table->string('address_landmark', 500)->nullable();
            $table->string('address_additional_information', 500)->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_profiles');
    }
};
