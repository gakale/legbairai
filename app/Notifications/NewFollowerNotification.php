<?php

namespace App\Notifications;

use App\Models\User; // Le modèle User de l'application (celui qui suit)
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage; // Pour la diffusion temps réel
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Http\Resources\UserResource; // Pour formater l'utilisateur qui suit

class NewFollowerNotification extends Notification implements ShouldQueue // Implémenter ShouldQueue pour le traitement en arrière-plan
{
    use Queueable;

    public User $follower;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $follower)
    {
        $this->follower = $follower;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable L'utilisateur qui reçoit la notification (celui qui est suivi)
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Canaux par lesquels la notification sera envoyée.
        // 'database' : stocke en DB (table notifications)
        // 'broadcast': diffuse via Reverb (ou autre driver de diffusion)
        // 'mail' : envoie un email (si vous configurez toMail())
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line($this->follower->username . ' a commencé à vous suivre !')
    //                 ->action('Voir son profil', url('/users/' . $this->follower->username)) // Adaptez l'URL
    //                 ->line('Merci d\'utiliser Le Gbairai!');
    // }

    /**
     * Get the array representation of the notification.
     * C'est ce qui est stocké dans la colonne 'data' de la table 'notifications'.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'follower_id' => $this->follower->id,
            'follower_username' => $this->follower->username,
            'follower_avatar_url' => $this->follower->avatar_url,
            'message' => $this->follower->username . ' a commencé à vous suivre.',
            // Vous pouvez ajouter une URL pour rediriger l'utilisateur lorsqu'il clique sur la notification
            'action_url' => route('api.v1.users.show.public', ['user' => $this->follower->id], false), // Exemple
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     * C'est ce qui est envoyé via le canal de diffusion (Reverb).
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\BroadcastMessage
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id, // L'ID de la notification elle-même (auto-généré)
            'read_at' => null, // Sera null car c'est une nouvelle notification
            'created_at' => now()->toIso8601String(),
            'data' => $this->toArray($notifiable), // Réutiliser les données de toArray()
        ]);
    }

    /**
     * Define which channel the notification should be broadcast on.
     * Par défaut, Laravel utilise un canal privé basé sur le notifiable.
     * Exemple : App.Models.User.{id}
     *
     * public function broadcastOn()
     * {
     *     return new PrivateChannel('App.Models.User.'.$this->notifiable->id);
     * }
     */
}