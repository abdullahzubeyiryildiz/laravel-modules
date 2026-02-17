<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('name'); // admin, manager, user, vb.
            $table->string('slug')->unique(); // admin, manager, user
            $table->string('display_name'); // Yönetici, Yönetici, Kullanıcı
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // Sistem rolü mü? (silinemez)
            $table->integer('level')->default(0); // Rol hiyerarşisi için (0=user, 10=admin)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'level']);

            // Unique constraint
            if (Schema::hasTable('tenants')) {
                $table->unique(['tenant_id', 'slug'], 'roles_tenant_slug_unique');
            } else {
                $table->unique('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
