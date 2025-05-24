<?php

declare(strict_types=1);

namespace Gbairai\Core\Http\Requests;

use Gbairai\Core\Enums\SpaceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Gbairai\Core\Models\Space; // Importer le modèle Space
use Gbairai\Core\Contracts\UserContract; // Pour l'autorisation

class StoreSpaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Implémenter la logique d'autorisation ici.
        // Par exemple, vérifier si l'utilisateur a le droit de créer un Space.
        // Pour l'instant, on autorise tout le monde si authentifié.
        /** @var UserContract|null $user */
        $user = $this->user();
        return $user !== null && $user->can('create', Space::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Ces règles sont très similaires à celles de CreateSpaceAction
        // On pourrait les centraliser si nécessaire, mais pour l'instant, la duplication est acceptable
        // car le FormRequest gère la requête HTTP et l'Action la logique métier.
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(SpaceType::values())],
            'ticket_price' => [
                Rule::requiredIf(fn () => $this->input('type') === SpaceType::PUBLIC_PAID->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'currency' => [
                Rule::requiredIf(fn () => $this->input('type') === SpaceType::PUBLIC_PAID->value),
                'nullable',
                'string',
                'size:3',
            ],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'is_recording_enabled_by_host' => ['sometimes', 'boolean'],
            'scheduled_at' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'after:now',
            ],
            // 'cover_image_file' => ['nullable', 'image', 'max:2048'], // Si upload de fichier
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du Space est obligatoire.',
            'type.required' => 'Le type de Space est obligatoire.',
            // ... autres messages personnalisés
        ];
    }

    /**
     * Prepare the data for validation.
     *
     *  Ici, on pourrait par exemple convertir 'is_recording_enabled_by_host' en booléen
     *  avant la validation si ce n'est pas déjà fait par le middleware TrimStrings et ConvertEmptyStringsToNull.
     *  Cependant, la validation 'boolean' de Laravel gère déjà "1", "0", "true", "false", true, false.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_recording_enabled_by_host')) {
            $this->merge([
                'is_recording_enabled_by_host' => filter_var($this->input('is_recording_enabled_by_host'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validated data from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Convertir type en Enum après validation
        if (isset($validated['type']) && is_string($validated['type'])) {
            $validated['type'] = SpaceType::from($validated['type']);
        }

        // S'assurer que is_recording_enabled_by_host est présent et booléen
        // même s'il n'était pas dans la requête (défaut à false)
        $validated['is_recording_enabled_by_host'] = $validated['is_recording_enabled_by_host'] ?? false;


        // Gérer 'scheduled_at' pour qu'il soit null si vide, ou un objet Carbon si présent
        if (isset($validated['scheduled_at']) && !empty($validated['scheduled_at'])) {
            // $validated['scheduled_at'] = Carbon::parse($validated['scheduled_at']); // Déjà un string formaté
        } else {
            $validated['scheduled_at'] = null;
        }


        return $validated;
    }
}