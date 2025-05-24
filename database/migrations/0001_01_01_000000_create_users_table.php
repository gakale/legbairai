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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Changer ici pour UUID
            $table->string('username')->unique(); // Ajouté
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number')->nullable()->unique(); // Ajouté
            $table->string('avatar_url')->nullable(); // Ajouté
            $table->string('cover_photo_url')->nullable(); // Ajouté
            $table->text('bio')->nullable(); // Ajouté
            $table->boolean('is_verified')->default(false); // Ajouté
            $table->boolean('is_premium')->default(false); // Ajouté
            $table->string('paystack_customer_id')->nullable()->index(); // Ajouté
            $table->rememberToken();
            $table->timestampsTz(); // Utiliser timestampTz pour la cohérence
        });
    
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index(); // Adapter pour UUID si la relation est avec un user_id UUID
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
