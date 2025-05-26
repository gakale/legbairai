<?php

namespace App\Providers;

use App\Events\DonationSucceededEvent; // Importer
use App\Listeners\SendDonationNotificationsListener; // Importer
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
// ... (autres imports)

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [ // Exemple par défaut
            SendEmailVerificationNotification::class,
        ],
        DonationSucceededEvent::class => [ // Ajouter ceci
            SendDonationNotificationsListener::class,
        ],
        // ... (autres événements et listeners que vous pourriez avoir)
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Ou true si vous préférez l'auto-découverte
    }
}