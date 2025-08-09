<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('order_items', function (Blueprint $t) {
            $t->string('kds_status')->default('pending')->index(); // pending, queued, preparing, ready, canceled, refunded, served
            $t->timestamp('kds_sent_at')->nullable()->index();
        });
    }
    public function down(): void {
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropColumn(['kds_status','kds_sent_at']);
        });
    }
};
