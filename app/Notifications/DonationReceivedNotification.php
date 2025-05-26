<?php

namespace App\Notifications;

use Gbairai\Core\Models\Donation; // Modèle du package
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Http\Resources\DonationResource; // Pour formater pour la diffusion

class DonationReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Donation $donation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Donation $donation)
    {
        // Charger les relations nécessaires pour l'affichage et la diffusion
        $this->donation = $donation->loadMissing(['donor', 'space']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable Le bénéficiaire (créateur)
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail']; // Ajouter 'mail' si vous voulez envoyer un email
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $amountFormatted = $this->donation->amount . ' ' . $this->donation->currency; // Utilise l'accesseur
        $donorName = $this->donation->donor->getUsername(); // Utilise la méthode du contrat/modèle

        $mailMessage = (new MailMessage)
            ->subject("🎉 Nouveau don reçu de {$donorName} !")
            ->greeting("Félicitations, {$notifiable->getUsername()} !") // $notifiable est le créateur
            ->line("Vous avez reçu un don de **{$amountFormatted}** de la part de **{$donorName}**.");

        if ($this->donation->space) {
            $mailMessage->line("Ce don a été effectué pendant votre Space : \"{$this->donation->space->title}\".");
        }

        // $mailMessage->action('Voir vos dons', url('/dashboard/donations')); // Lien vers le tableau de bord
        $mailMessage->line("Continuez votre excellent travail sur Le Gbairai !");

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification (pour la base de données).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'donation_id' => $this->donation->id,
            'donor_id' => $this->donation->donor_user_id,
            'donor_username' => $this->donation->donor->getUsername(),
            'donor_avatar_url' => $this->donation->donor->avatar_url,
            'amount_subunit' => $this->donation->amount_subunit,
            'amount_formatted' => $this->donation->amount, // Utilise l'accesseur
            'currency' => $this->donation->currency,
            'space_id' => $this->donation->space_id,
            'space_title' => $this->donation->space?->title,
            'message' => "Vous avez reçu un don de {$this->donation->amount} {$this->donation->currency} de {$this->donation->donor->getUsername()}.",
            'action_url' => null, // Ou un lien vers la page de détails du don
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
            'data' => $this->toArray($notifiable), // Réutiliser les données de toArray()
            // On pourrait envoyer des données plus spécifiques ou formatées pour le broadcast si nécessaire
            // Par exemple, pour une animation visuelle du don dans le Space
            'broadcast_specific_data' => [
                'type' => 'donation_received_alert', // Pour que le client sache comment traiter cet événement
                'donation' => (new DonationResource($this->donation))->toArray(request()),
            ]
        ]);
    }
}