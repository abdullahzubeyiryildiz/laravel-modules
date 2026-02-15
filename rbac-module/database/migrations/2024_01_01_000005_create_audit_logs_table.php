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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Action details
            $table->string('action'); // created, updated, deleted, viewed, exported
            $table->string('entity'); // User, Order, Product, File
            $table->unsignedBigInteger('entity_id')->nullable(); // İlgili kayıt ID'si

            // Request details
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('url')->nullable();

            // Data
            $table->json('old_values')->nullable(); // Değişiklik öncesi
            $table->json('new_values')->nullable(); // Değişiklik sonrası
            $table->json('meta')->nullable(); // Ek bilgiler

            // Performance
            $table->integer('execution_time_ms')->nullable(); // İşlem süresi (ms)

            $table->timestamp('created_at');

            // Indexes (çok kritik - sorgu performansı için)
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'user_id', 'created_at']);
            $table->index(['tenant_id', 'entity', 'entity_id']);
            $table->index(['tenant_id', 'action', 'created_at']);
            $table->index('created_at'); // Global sorgular için
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
