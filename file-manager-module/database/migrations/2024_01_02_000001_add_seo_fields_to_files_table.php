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
        Schema::table('files', function (Blueprint $table) {
            // SEO alanları
            $table->string('alt_text')->nullable()->after('original_name');

            // Resim boyutları (meta'da da var ama hızlı erişim için)
            $table->integer('width')->nullable()->after('alt_text');
            $table->integer('height')->nullable()->after('width');

            // Index
            $table->index('alt_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['alt_text']);
            $table->dropColumn([
                'alt_text',
                'width',
                'height',
            ]);
        });
    }
};
