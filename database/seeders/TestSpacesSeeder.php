<?php

namespace Database\Seeders;

use App\Models\User;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Enums\SpaceType;
use Gbairai\Core\Models\Space;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TestSpacesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Recuperer les utilisateurs de test
        $users = User::whereIn('username', ['testuser1', 'testuser2', 'testuser3', 'donationtester'])->get();
        
        if ($users->isEmpty()) {
            $this->command->error('Aucun utilisateur de test trouve. Veuillez executer d\'abord TestUsersSeeder.');
            return;
        }

        // Creer des espaces pour certains utilisateurs
        foreach ($users as $index => $user) {
            // Creer 2 espaces pour chaque utilisateur
            for ($i = 1; $i <= 2; $i++) {
                $spaceNumber = ($index * 2) + $i;
                $isLive = $i === 1; // Premier espace est en direct, deuxieme est planifie
                
                $space = new Space([
                    'host_user_id' => $user->id,
                    'title' => "Espace test {$spaceNumber} de {$user->username}",
                    'description' => "Description de l'espace test {$spaceNumber}",
                    'cover_image_url' => "https://picsum.photos/id/" . ($spaceNumber + 10) . "/800/400",
                    'status' => $isLive ? SpaceStatus::LIVE : SpaceStatus::SCHEDULED,
                    'type' => $spaceNumber % 3 === 0 ? SpaceType::PRIVATE_INVITE : SpaceType::PUBLIC_FREE,
                    'ticket_price' => $spaceNumber % 3 === 0 ? 5.99 : null,
                    'currency' => $spaceNumber % 3 === 0 ? 'XOF' : null,
                    'max_participants' => 50,
                    'is_recording_enabled_by_host' => true,
                    'scheduled_at' => $isLive ? null : Carbon::now()->addDays($spaceNumber),
                    'started_at' => $isLive ? Carbon::now()->subHours(1) : null,
                    'ended_at' => null,
                    'duration_seconds' => null,
                ]);
                
                $space->save();
                
                $this->command->info("Espace '{$space->title}' cree pour {$user->username}");
            }
        }
        
        // Creer un espace termine pour le test des clips audio
        $hostUser = $users->firstWhere('username', 'testuser2');
        if ($hostUser) {
            $space = new Space([
                'host_user_id' => $hostUser->id,
                'title' => "Espace termine pour test de clips audio",
                'description' => "Cet espace est termine et dispose d'un enregistrement pour tester les clips audio",
                'cover_image_url' => "https://picsum.photos/id/100/800/400",
                'status' => SpaceStatus::ENDED,
                'type' => SpaceType::PUBLIC_FREE,
                'max_participants' => 50,
                'is_recording_enabled_by_host' => true,
                'started_at' => Carbon::now()->subDays(1),
                'ended_at' => Carbon::now()->subDays(1)->addHours(2),
                'duration_seconds' => 7200, // 2 heures
            ]);
            
            $space->save();
            
            // Ici, on pourrait creer un enregistrement fictif pour cet espace
            // TODO: Creer un SpaceRecording pour ce Space
            
            $this->command->info("Espace termine '{$space->title}' cree pour {$hostUser->username}");
        }

        $this->command->info('Espaces de test crees avec succes!');
    }
}
