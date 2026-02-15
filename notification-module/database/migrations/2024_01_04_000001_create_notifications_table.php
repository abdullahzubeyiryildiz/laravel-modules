<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Laravel'in standart notifications tablosunu oluşturur ve custom alanlar ekler.
     * Eğer tablo zaten varsa, sadece custom alanları ekler.
     */
    public function up(): void
    {
        // Laravel'in standart notifications tablosu yoksa oluştur
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                // Laravel'in standart notification yapısı
                $table->uuid('id')->primary();
                $table->string('type'); // Notification class name
                $table->morphs('notifiable'); // notifiable_type, notifiable_id
                $table->text('data'); // JSON data
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // Custom alanları ekle (eğer yoksa)
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->nullable()->after('data');
            }
            if (!Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('title');
            }
            if (!Schema::hasColumn('notifications', 'action_text')) {
                $table->string('action_text')->nullable()->after('action_url');
            }
            if (!Schema::hasColumn('notifications', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index()->after('read_at');
            }

            // Indexes (eğer yoksa)
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('notifications');

            if (!isset($indexesFound['notifications_notifiable_type_notifiable_id_read_at_index'])) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            }
            if (!isset($indexesFound['notifications_tenant_id_read_at_created_at_index'])) {
                $table->index(['tenant_id', 'read_at', 'created_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Custom alanları kaldır
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['tenant_id', 'title', 'action_url', 'action_text', 'expires_at']);
        });

        // Not: Laravel'in standart notifications tablosunu silmiyoruz
        // Çünkü başka yerlerde kullanılıyor olabilir
    }
};
