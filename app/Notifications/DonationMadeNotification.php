<?php

namespace App\Notifications;

use Gbairai\Core\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage; // Si on veut aussi notifier le donneur en temps réel
use Illuminate\Notifications\Notification;

class DonationMadeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Donation $donation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Donation $donation)
    {
        $this->donation = $donation->loadMissing(['recipient', 'space']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable Le donateur
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Le donneur reçoit principalement un email de confirmation/remerciement.
        // Une notification en DB/Broadcast est optionnelle pour le donneur.
        return ['mail', 'database']; // Optionnel : 'broadcast'
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $amountFormatted = $this->donation->amount . ' ' . $this->donation->currency;
        $recipientName = $this->donation->recipient->getUsername();

        $mailMessage = (new MailMessage)
            ->subject('Merci pour votre don sur Le Gbairai !')
            ->greeting("Bonjour {$notifiable->getUsername()},") // $notifiable est le donateur
            ->line("Merci beaucoup pour votre don de **{$amountFormatted}** à **{$recipientName}** !")
            ->line("Votre soutien aide les créateurs à continuer de produire du contenu de qualité.");

        if ($this->donation->space) {
            $mailMessage->line("Ce don a été effectué pendant le Space : \"{$this->donation->space->title}\".");
        }

        // $mailMessage->action('Voir vos dons effectués', url('/profile/donations-made'));
        $mailMessage->line("Merci d'utiliser Le Gbairai !");

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification (pour la base de données).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'donation_id' => $this->donation->id,
            'recipient_id' => $this->donation->recipient_user_id,
            'recipient_username' => $this->donation->recipient->getUsername(),
            'amount_subunit' => $this->donation->amount_subunit,
            'amount_formatted' => $this->donation->amount,
            'currency' => $this->donation->currency,
            'message' => "Merci pour votre don de {$this->donation->amount} {$this->donation->currency} à {$this->donation->recipient->getUsername()}.",
            'action_url' => null,
        ];
    }

    /**
     * Get the broadcastable representation of the notification (si on choisit de diffuser au donneur).
     */
    // public function toBroadcast(object $notifiable): BroadcastMessage
    // {
    //     return new BroadcastMessage([
    //         'id' => $this->id,
    //         'read_at' => null,
    //         'created_at' => now()->toIso8601String(),
    //         'data' => $this->toArray($notifiable),
    //     ]);
    // }
}