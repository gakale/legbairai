<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pour PostgreSQL, nous devons d'abord supprimer les contraintes et index
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Supprimer l'index existant
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
            
            // Supprimer la contrainte de clé étrangère si elle existe
            // Cela dépend de votre schéma, vous pouvez commenter cette ligne si non applicable
            // $table->dropForeign(['tokenable_id']);
        });
        
        // Maintenant, modifier les colonnes pour utiliser UUID
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Modifier la colonne tokenable_id pour accepter les UUID (string)
            $table->string('tokenable_id', 36)->change();
            
            // Recréer l'index
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Supprimer l'index existant
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
        });
        
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Revenir à bigint pour tokenable_id
            $table->bigInteger('tokenable_id')->change();
            
            // Recréer l'index
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }
};
