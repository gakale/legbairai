<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création des utilisateurs de test...');
        
        // Créer 5 utilisateurs pour les tests
        for ($i = 1; $i <= 5; $i++) {
            $email = "testuser$i@example.com";
            $username = "testuser$i";
            
            // Vérifier si l'utilisateur existe déjà
            if (User::where('email', $email)->orWhere('username', $username)->exists()) {
                $this->command->info("L'utilisateur $username existe déjà, ignoré.");
                continue;
            }
            
            User::create([
                'name' => "Test User $i",
                'username' => $username,
                'email' => $email,
                'password' => Hash::make('password'),
                'phone_number' => "+123456789$i",
                'avatar_url' => "https://ui-avatars.com/api/?name=Test+User+$i",
                'bio' => "Bio de l'utilisateur de test $i",
                'is_verified' => true,
                'is_premium' => $i % 2 === 0, // Utilisateurs pairs sont premium
            ]);
            
            $this->command->info("Utilisateur $username créé.");
        }

        // Créer un utilisateur spécifique pour les tests de donation
        $donationTester = "donationtester";
        if (!User::where('username', $donationTester)->exists()) {
            User::create([
                'name' => "Donation Tester",
                'username' => $donationTester,
                'email' => "donation@example.com",
                'password' => Hash::make('password'),
                'phone_number' => "+9876543210",
                'avatar_url' => "https://ui-avatars.com/api/?name=Donation+Tester",
                'bio' => "Utilisateur pour tester les donations",
                'is_verified' => true,
                'is_premium' => true,
            ]);
            $this->command->info("Utilisateur $donationTester créé.");
        } else {
            $this->command->info("L'utilisateur $donationTester existe déjà, ignoré.");
        }

        // Créer un utilisateur admin pour les tests
        $adminUser = "adminuser";
        if (!User::where('username', $adminUser)->exists()) {
            User::create([
                'name' => "Admin User",
                'username' => $adminUser,
                'email' => "admin@example.com",
                'password' => Hash::make('password'),
                'phone_number' => "+1122334455",
                'avatar_url' => "https://ui-avatars.com/api/?name=Admin+User",
                'bio' => "Administrateur pour les tests",
                'is_verified' => true,
                'is_premium' => true,
            ]);
            $this->command->info("Utilisateur $adminUser créé.");
        } else {
            $this->command->info("L'utilisateur $adminUser existe déjà, ignoré.");
        }

        $this->command->info('Traitement des utilisateurs de test terminé!');
    }
}
