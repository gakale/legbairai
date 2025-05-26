<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Messages;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceMessage;
use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Support\Facades\Gate; // Pour vérifier si l'utilisateur peut envoyer un message
use Illuminate\Validation\ValidationException; // Pour la validation du contenu
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use App\Events\NewSpaceMessageEvent; // Importer


/**
 * Class SendSpaceMessageAction
 *
 * Creates and saves a new message in a Space.
 */
class SendSpaceMessageAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $sender The user sending the message.
     * @param Space $space The space where the message is sent.
     * @param string $content The content of the message.
     * @return SpaceMessage The newly created message.
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function execute(UserContract $sender, Space $space, string $content): SpaceMessage
    {
        // 1. Autorisation: L'utilisateur doit être un participant actif du Space pour envoyer un message.
        //    Nous pourrions avoir une policy SpacePolicy@sendMessage ou vérifier directement ici.
        /** @var SpaceParticipant|null $participantRecord */
        $participantRecord = $space->participants()
            ->where('user_id', $sender->getId())
            ->whereNull('left_at')
            ->first();

        if (!$participantRecord && $space->host_user_id !== $sender->getId()) { // L'hôte peut toujours envoyer, même s'il n'a pas d'entrée "participant" formelle
            throw new \Illuminate\Auth\Access\AuthorizationException("Vous devez être un participant actif pour envoyer un message dans ce Space.");
        }

        // 2. Vérifier si le Space est LIVE (on ne peut chatter que dans un Space en direct)
        if ($space->status !== \Gbairai\Core\Enums\SpaceStatus::LIVE) {
            throw new RuntimeException("Les messages ne peuvent être envoyés que dans un Space en direct.");
        }

        // 3. Validation du contenu du message
        $this->validateContent($content);

        // 4. Création du message
        /** @var SpaceMessage $message */
        $message = app(config('gbairai-core.models.space_message'))->create([
            'space_id' => $space->id,
            'user_id' => $sender->getId(),
            'content' => $content,
            'is_pinned' => false, // Les messages ne sont pas épinglés par défaut
        ]);

        // Charger la relation utilisateur pour un accès facile après la création (utile pour l'événement)
        $message->load('user');

        // 5. Déclencher un événement NewSpaceMessageEvent (sera fait à l'étape suivante)
        NewSpaceMessageEvent::dispatch($message);

        return $message;
    }

    /**
     * Validate the message content.
     *
     * @param string $content
     * @throws ValidationException
     */
    protected function validateContent(string $content): void
    {
        Validator::make(['content' => $content], [
            'content' => ['required', 'string', 'min:1', 'max:1000'], // Ajustez la longueur max si nécessaire
        ])->validate();
    }
}