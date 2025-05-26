<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Exécuter les seeders de test dans l'ordre
        $this->call([
            TestUsersSeeder::class,          // Crée d'abord les utilisateurs
            TestSpacesSeeder::class,         // Puis crée les espaces liés à ces utilisateurs
            TestSpaceRecordingsSeeder::class, // Enfin crée les enregistrements pour les espaces terminés
        ]);
    }
}
