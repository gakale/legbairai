<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Spaces;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Enums\SpaceType;
use Gbairai\Core\Models\Space;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Class CreateSpaceAction
 *
 * Creates a new Space.
 */
class CreateSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $host The user creating the space.
     * @param array<string, mixed> $data Data for creating the space.
     *        Expected keys: 'title', 'description' (optional), 'type',
     *                       'ticket_price' (if type is PUBLIC_PAID), 'currency' (if type is PUBLIC_PAID),
     *                       'max_participants' (optional), 'is_recording_enabled_by_host' (optional),
     *                       'scheduled_at' (if status is SCHEDULED).
     * @return Space The newly created Space.
     * @throws ValidationException
     */
    public function execute(UserContract $host, array $data): Space
    {
        // Utiliser les données validées directement
        $validatedData = $this->validateData($data);

        /** @var Space $space */
        $space = app(config('gbairai-core.models.space'))->make();

        $space->fill([
            'title' => $validatedData['title'],
            'description' => Arr::get($validatedData, 'description'),
            'type' => $validatedData['type'], // C'est déjà un Enum ici
            'status' => Arr::get($validatedData, 'scheduled_at') ? SpaceStatus::SCHEDULED : SpaceStatus::LIVE,
            'ticket_price' => Arr::get($validatedData, 'ticket_price'),
            'currency' => Arr::get($validatedData, 'currency'),
            'max_participants' => Arr::get($validatedData, 'max_participants'),
            'is_recording_enabled_by_host' => Arr::get($validatedData, 'is_recording_enabled_by_host', false),
            'scheduled_at' => Arr::get($validatedData, 'scheduled_at'),
        ]);

        // Comme UserContract peut ne pas être un modèle Eloquent, nous utilisons l'ID
        $space->host_user_id = $host->getId();
        $space->save();

        // Potentiellement, ajouter l'hôte comme premier participant ici
        // ou déclencher un événement.

        return $space;
    }

    /**
     * Validate the input data.
     *
     * @param array<string, mixed> $inputData
     * @return array<string, mixed> Validated data with enums cast.
     * @throws ValidationException
     */
    protected function validateData(array $inputData): array
    {
        $validated = Validator::make($inputData, [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(SpaceType::values())],
            'ticket_price' => [
                Rule::requiredIf(fn () => Arr::get($inputData, 'type') === SpaceType::PUBLIC_PAID->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'currency' => [
                Rule::requiredIf(fn () => Arr::get($inputData, 'type') === SpaceType::PUBLIC_PAID->value),
                'nullable',
                'string',
                'size:3', // ex: XOF, USD
            ],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'is_recording_enabled_by_host' => ['sometimes', 'boolean'],
            'scheduled_at' => [
                'nullable',
                'date_format:Y-m-d H:i:s', // Être explicite sur le format attendu
                'after:now',
            ],
        ])->validate();

        // Convertir les chaînes en Enums après validation
        if (isset($validated['type']) && is_string($validated['type'])) {
            $validated['type'] = SpaceType::from($validated['type']);
        }
        
        // Assurer que is_recording_enabled_by_host est un booléen
        if (isset($validated['is_recording_enabled_by_host'])) {
            $validated['is_recording_enabled_by_host'] = filter_var($validated['is_recording_enabled_by_host'], FILTER_VALIDATE_BOOLEAN);
        } else {
            // S'assurer que la clé existe même si non fournie et 'sometimes' est utilisé
            $validated['is_recording_enabled_by_host'] = false;
        }

        return $validated;
    }
}
