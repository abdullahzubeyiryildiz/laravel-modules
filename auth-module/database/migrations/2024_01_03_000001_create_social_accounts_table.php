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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider'); // google, facebook, github, twitter, vb.
            $table->string('provider_id'); // OAuth provider'dan gelen ID
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->json('provider_data')->nullable(); // Provider'dan gelen ek veriler
            $table->timestamps();

            // Indexes
            $table->index(['provider', 'provider_id']);
            $table->index('user_id');
            $table->index('email');

            // Unique constraint
            $table->unique(['provider', 'provider_id'], 'social_accounts_provider_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
