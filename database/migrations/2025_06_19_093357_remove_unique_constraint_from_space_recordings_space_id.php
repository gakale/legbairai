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
        Schema::table('space_recordings', function (Blueprint $table) {
            // Supprimer la contrainte unique sur space_id pour permettre plusieurs enregistrements par espace
            $table->dropUnique(['space_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('space_recordings', function (Blueprint $table) {
            // Rétablir la contrainte unique sur space_id
            $table->unique('space_id');
        });
    }
};
