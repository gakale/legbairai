<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Spaces;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Enums\SpaceType; // Assurez-vous que c'est importé
use Gbairai\Core\Models\Space;
use Illuminate\Support\Arr;
// Plus besoin de Validator, Rule, ValidationException ici si on fait confiance aux données entrantes

class CreateSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $host The user creating the space.
     * @param array<string, mixed> $validatedData Data for creating the space,
     *        assumed to be pre-validated and correctly typed (e.g., from a FormRequest).
     *        'type' should be a SpaceType Enum instance.
     *        'is_recording_enabled_by_host' should be a boolean.
     * @return Space The newly created Space.
     */
    public function execute(UserContract $host, array $validatedData): Space
    {
        // On fait confiance que $validatedData contient les bons types (ex: SpaceType enum)
        // grâce au FormRequest ou à une validation en amont.

        /** @var Space $space */
        $space = app(config('gbairai-core.models.space'))->make();

        // Si scheduled_at est fourni, le statut est SCHEDULED, sinon LIVE par défaut.
        // Le FormRequest s'assure que scheduled_at est un string date valide ou null.
        $status = Arr::get($validatedData, 'scheduled_at') ? SpaceStatus::SCHEDULED : SpaceStatus::LIVE;

        $space->fill([
            'title' => $validatedData['title'],
            'description' => Arr::get($validatedData, 'description'),
            'type' => $validatedData['type'], // Doit être un objet SpaceType
            'status' => $status,
            'ticket_price' => Arr::get($validatedData, 'ticket_price'),
            'currency' => Arr::get($validatedData, 'currency'),
            'max_participants' => Arr::get($validatedData, 'max_participants'),
            'is_recording_enabled_by_host' => Arr::get($validatedData, 'is_recording_enabled_by_host', false),
            'scheduled_at' => Arr::get($validatedData, 'scheduled_at'), // Doit être un string date valide ou null
        ]);

        $space->host_user_id = $host->getId();
        $space->save();

        // TODO: Éventuellement, ajouter l'hôte comme premier participant ici (via une autre action/service)
        // Appeler une action AddParticipantToSpaceAction($space, $host, SpaceParticipantRole::HOST_EQUIVALENT_IF_NEEDED)
        // Ou déclencher un événement SpaceCreated qui sera écouté pour ajouter l'hôte.

        return $space;
    }
}
