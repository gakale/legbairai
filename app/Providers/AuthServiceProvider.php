<?php

namespace App\Providers;

use Gbairai\Core\Models\Space; // Importer le modèle Space du package
use App\Policies\SpacePolicy;   // Importer la Policy que nous avons créée
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
// use Illuminate\Support\Facades\Gate; // Décommentez si vous avez besoin d'utiliser Gate directement pour des permissions plus granulaires

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Space::class => SpacePolicy::class,
        // Vous ajouterez d'autres mappings modèle-policy ici au fur et à mesure
        // Exemple: \App\Models\Comment::class => \App\Policies\CommentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // La méthode registerPolicies() est appelée par Laravel pour enregistrer
        // les policies définies dans la propriété $policies.
        // Vous n'avez généralement pas besoin d'appeler $this->registerPolicies(); explicitement ici.

        // Si vous avez des permissions basées sur Gate qui ne sont pas liées à des modèles,
        // vous les définiriez ici. Par exemple:
        // Gate::define('view-admin-dashboard', function ($user) {
        //     return $user->isAdmin();
        // });
    }
}
