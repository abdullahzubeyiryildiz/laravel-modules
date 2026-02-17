<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * roles tablosundan tenant_id kaldır; roller global (user_id / user_roles ile bağlı).
     */
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        if (!Schema::hasColumn('roles', 'tenant_id')) {
            return;
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_tenant_slug_unique');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropIndex('roles_tenant_id_is_active_index');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('slug', 'roles_slug_unique');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_slug_unique');
            $table->dropIndex(['is_active']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'slug'], 'roles_tenant_slug_unique');
        });
    }
};
