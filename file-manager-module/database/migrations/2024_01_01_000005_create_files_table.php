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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Storage details
            $table->string('disk')->default('s3'); // s3, local, r2, minio
            $table->string('bucket')->nullable(); // S3 bucket adı
            $table->string('path'); // t/{tenant_id}/2026/02/{uuid}.{ext}
            $table->string('url')->nullable(); // Public URL (eğer public ise)

            // File metadata
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('hash_sha256', 64)->nullable(); // Deduplication için
            $table->string('extension', 10)->nullable();

            // Access control
            $table->boolean('is_private')->default(true);
            $table->string('access_token')->nullable()->unique(); // Signed URL için token

            // File type specific
            $table->string('file_type')->nullable(); // image, document, video, audio, other
            $table->json('meta')->nullable(); // width, height (image), page_count (pdf), duration (video), vb.

            // Relations
            $table->string('related_entity')->nullable(); // User, Product, Order
            $table->unsignedBigInteger('related_entity_id')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable(); // Geçici dosyalar için
            $table->timestamps();
            $table->softDeletes();

            // Indexes (çok kritik)
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'owner_user_id']);
            $table->index(['tenant_id', 'file_type']);
            $table->index(['tenant_id', 'is_private']);
            $table->index(['tenant_id', 'related_entity', 'related_entity_id']);
            $table->index('hash_sha256'); // Deduplication için
            $table->index('access_token');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
