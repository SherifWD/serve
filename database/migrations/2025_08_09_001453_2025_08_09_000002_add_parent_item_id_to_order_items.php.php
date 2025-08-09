<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            $t->unsignedBigInteger('parent_item_id')->nullable()->after('order_id');
            $t->foreign('parent_item_id')->references('id')->on('order_items')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropForeign(['parent_item_id']);
            $t->dropColumn('parent_item_id');
        });
    }
};
