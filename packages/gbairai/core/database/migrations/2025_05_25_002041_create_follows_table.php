<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $usersTable;
    protected string $followsTable;

    public function __construct()
    {
        $this->usersTable = config('gbairai-core.table_names.users', 'users');
        $this->followsTable = config('gbairai-core.table_names.follows', 'follows');
    }

    public function up(): void
    {
        Schema::create($this->followsTable, function (Blueprint $table) {
            // Option A: ID UUID séparé pour chaque enregistrement de suivi
            $table->uuid('id')->primary();
            $table->foreignUuid('follower_user_id')->constrained($this->usersTable)->cascadeOnDelete();
            $table->foreignUuid('following_user_id')->constrained($this->usersTable)->cascadeOnDelete();
            $table->timestampsTz(); // created_at et updated_at

            // Clé unique pour s'assurer qu'un utilisateur ne peut pas suivre la même personne plusieurs fois
            $table->unique(['follower_user_id', 'following_user_id']);

            // Index pour optimiser les recherches
            $table->index('following_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->followsTable);
    }
};
