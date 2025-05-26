<?php

namespace App\Listeners;

use App\Events\DonationSucceededEvent;
use Illuminate\Contracts\Queue\ShouldQueue; // Pour que le listener soit en file d'attente
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\DonationReceivedNotification;
use App\Notifications\DonationMadeNotification;
use App\Models\User as AppUserModel; // Modèle User de l'application

class SendDonationNotificationsListener implements ShouldQueue // Implémenter ShouldQueue
{
    use InteractsWithQueue; // Utiliser ce trait

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DonationSucceededEvent $event): void
    {
        $donation = $event->donation;

        // S'assurer que le donneur et le bénéficiaire sont des instances du modèle User de l'application
        // qui peuvent recevoir des notifications.
        if ($donation->recipient instanceof AppUserModel) {
            $donation->recipient->notify(new DonationReceivedNotification($donation));
        }

        if ($donation->donor instanceof AppUserModel) {
            $donation->donor->notify(new DonationMadeNotification($donation));
        }
    }
}