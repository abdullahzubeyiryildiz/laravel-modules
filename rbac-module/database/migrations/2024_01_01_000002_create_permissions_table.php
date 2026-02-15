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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('name'); // users.view, users.create, users.edit, users.delete
            $table->string('slug')->unique(); // users.view, users.create
            $table->string('group')->nullable(); // users, orders, products
            $table->string('display_name'); // Kullanıcıları Görüntüle
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'group']);
            $table->index(['tenant_id', 'is_active']);

            // Unique constraint
            if (Schema::hasTable('tenants')) {
                $table->unique(['tenant_id', 'slug'], 'permissions_tenant_slug_unique');
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
        Schema::dropIfExists('permissions');
    }
};
