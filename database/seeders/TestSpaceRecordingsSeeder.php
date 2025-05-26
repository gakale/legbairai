<?php

namespace Database\Seeders;

use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceRecording;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TestSpaceRecordingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les espaces terminés
        $endedSpaces = Space::where('status', 'ended')->get();
        
        $this->command->info('Recherche des espaces terminés...');
        
        if ($endedSpaces->isEmpty()) {
            $this->command->error('Aucun espace terminé trouvé. Veuillez exécuter d\'abord TestSpacesSeeder.');
            return;
        }

        foreach ($endedSpaces as $space) {
            // Vérifier si un enregistrement existe déjà pour cet espace
            $existingRecording = SpaceRecording::where('space_id', $space->id)->first();
            
            if ($existingRecording) {
                $this->command->info("L'espace {$space->title} a déjà un enregistrement.");
                continue;
            }
            
            // Créer un enregistrement fictif pour cet espace
            $recording = new SpaceRecording([
                'space_id' => $space->id,
                'recording_url' => 'https://example.com/recordings/' . $space->id . '.mp3',
                'duration_seconds' => $space->duration_seconds ?? 3600, // 1 heure par défaut
                'file_size_mb' => rand(10, 50), // Entre 10MB et 50MB
                'is_publicly_accessible' => true,
            ]);
            
            $recording->save();
            
            $this->command->info("Enregistrement créé pour l'espace '{$space->title}'.");
        }

        $this->command->info('Enregistrements de test créés avec succès!');
    }
}
