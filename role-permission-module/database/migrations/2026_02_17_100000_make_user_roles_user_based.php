<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * user_roles: tenant_id'den bağımsız, sadece user_id ile rol ilişkisi.
     * Bir kullanıcının birden fazla rolü olabilir; tenant'a göre çalışmaz.
     */
    public function up(): void
    {
        if (!Schema::hasTable('user_roles')) {
            return;
        }

        // Eski unique yoksa (yeni kurulum zaten doğru yapıda) atla
        try {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropUnique('user_roles_unique');
            });
        } catch (\Throwable $e) {
            return;
        }

        // Aynı (user_id, role_id) için tek satır bırak
        $duplicates = DB::table('user_roles')
            ->select('user_id', 'role_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id', 'role_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            DB::table('user_roles')
                ->where('user_id', $row->user_id)
                ->where('role_id', $row->role_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        DB::table('user_roles')->update(['tenant_id' => null]);

        try {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropIndex('user_roles_user_id_tenant_id_index');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('user_roles', function (Blueprint $table) {
            $table->unique(['user_id', 'role_id'], 'user_roles_user_role_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_roles')) {
            return;
        }

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropUnique('user_roles_user_role_unique');
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $table->unique(['user_id', 'role_id', 'tenant_id'], 'user_roles_unique');
            $table->index(['user_id', 'tenant_id']);
        });
    }
};
