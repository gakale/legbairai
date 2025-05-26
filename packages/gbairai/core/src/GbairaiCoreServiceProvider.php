<?php

declare(strict_types=1);

namespace Gbairai\Core;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
// use Gbairai\Core\Commands\GbairaiCoreCommand; // Exemple si vous aviez une commande

class GbairaiCoreServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('gbairai-core')
            ->hasConfigFile()
            // ->hasViews() // Si votre package fournit des vues Blade
            ->hasMigrations([
                'create_spaces_table', 
                'create_space_participants_table', 
                'create_space_messages_table', 
                'create_space_recordings_table',
                'create_follows_table',
                'create_audio_clips_table',
                // Ajoutez d'autres migrations ici au fur et à mesure
            ])
            // ->hasCommand(GbairaiCoreCommand::class) // Si vous ajoutez des commandes Artisan
            ->hasTranslations(); // Si votre package fournit des fichiers de traduction
            // ->hasRoute('web') // Si votre package fournit des routes web
            // ->hasRoute('api'); // Si votre package fournit des routes api
    }

    public function packageRegistered(): void
    {
        // Code à exécuter lorsque le package est enregistré (avant la méthode boot)
        // Exemple: $this->app->bind('my-service', function ($app) { ... });
    }

    public function packageBooted(): void
    {
        // Code à exécuter lorsque le package est "booté" (après la méthode register de tous les providers)
        // Exemple: Charger les relations polymorphiques, enregistrer des policies, etc.

        // Configurer l'utilisation des UUIDs pour les modèles si c'est une convention globale du package
        // Model::preventLazyLoading(! app()->isProduction()); // Exemple d'une bonne pratique
        // Model::unguard(); // Soyez prudent avec ceci
    }
}
