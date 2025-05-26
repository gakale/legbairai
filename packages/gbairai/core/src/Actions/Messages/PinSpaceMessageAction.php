<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Messages;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Space; // Importer Space
use Gbairai\Core\Models\SpaceMessage;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use App\Events\MessagePinnedStatusChangedEvent;

/**
 * Class PinSpaceMessageAction
 *
 * Pins or unpins a message in a Space.
 */
class PinSpaceMessageAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $actor The user performing the action (host/co-host).
     * @param SpaceMessage $message The message to pin or unpin.
     * @param bool $shouldBePinned True to pin, false to unpin.
     * @return SpaceMessage The updated message.
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws RuntimeException
     */
    public function execute(UserContract $actor, SpaceMessage $message, bool $shouldBePinned): SpaceMessage
    {
        // 1. Récupérer le Space associé au message pour l'autorisation
        /** @var Space $space */
        $space = $message->space; // Suppose que la relation 'space' est chargée ou chargeable

        if (!$space) {
            // Ce cas ne devrait pas arriver si le message est valide
            throw new RuntimeException("Le message n'est associé à aucun Space.");
        }

        // 2. Autorisation: L'acteur doit pouvoir gérer les participants/messages (ex: SpacePolicy@pinMessage)
        Gate::forUser($actor)->authorize('pinMessage', $space);
        // Ou une policy plus spécifique : Gate::forUser($actor)->authorize('togglePin', $message);

        // 3. Vérifier si le message appartient bien au Space (double sécurité)
        if ($message->space_id !== $space->id) {
            throw new RuntimeException("Ce message n'appartient pas au Space indiqué pour l'autorisation.");
        }

        // 4. Mettre à jour l'état d'épinglage
        if ($message->is_pinned === $shouldBePinned) {
            // Aucune action nécessaire si l'état est déjà celui désiré
            return $message;
        }

        // Si on épingle un nouveau message, on pourrait vouloir détacher l'ancien message épinglé
        // (si la règle est "un seul message épinglé à la fois").
        if ($shouldBePinned) {
            $space->messages()->where('is_pinned', true)->update(['is_pinned' => false]);
        }

        $message->is_pinned = $shouldBePinned;
        $message->save();

        // Charger l'utilisateur pour l'événement
        $message->loadMissing('user');

        // 5. Déclencher un événement MessagePinnedStatusChangedEvent (sera fait à l'étape suivante)
        MessagePinnedStatusChangedEvent::dispatch($message);

        return $message;
    }
}