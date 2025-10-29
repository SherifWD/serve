<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_unique');
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('key')->nullable()->after('tenant_id');
            $table->string('display_name')->nullable()->after('key');
            $table->string('description')->nullable()->after('display_name');
            $table->string('guard_name')->default('web')->after('description');
            $table->boolean('is_default')->default(false)->after('guard_name');
            $table->json('metadata')->nullable()->after('is_default');
            $table->unique(['tenant_id', 'key'], 'roles_tenant_key_unique');
        });

        DB::table('roles')->whereNull('key')->update(['key' => DB::raw('name')]);
        DB::statement('ALTER TABLE roles MODIFY `key` VARCHAR(255) NOT NULL');

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_name_unique');
            $table->string('key')->nullable()->after('id');
            $table->string('display_name')->nullable()->after('key');
            $table->string('description')->nullable()->after('display_name');
            $table->string('group')->nullable()->after('description');
            $table->string('guard_name')->default('web')->after('group');
            $table->json('metadata')->nullable()->after('guard_name');
            $table->unique('key');
        });

        DB::table('permissions')->whereNull('key')->update(['key' => DB::raw('name')]);
        DB::statement('ALTER TABLE permissions MODIFY `key` VARCHAR(255) NOT NULL');

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['permission_id', 'role_id'], 'permission_role_unique');
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('tenant_id');
            $table->json('metadata')->nullable()->after('assigned_at');
            $table->unique(['tenant_id', 'role_id', 'user_id'], 'role_user_tenant_role_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropUnique('role_user_tenant_role_user_unique');
            $table->dropColumn(['metadata', 'assigned_at']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::dropIfExists('permission_role');

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_key_unique');
            $table->dropColumn(['metadata', 'guard_name', 'group', 'description', 'display_name', 'key']);
            $table->unique('name');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_tenant_key_unique');
            $table->dropColumn(['metadata', 'is_default', 'guard_name', 'description', 'display_name', 'key']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('name');
        });
    }
};
