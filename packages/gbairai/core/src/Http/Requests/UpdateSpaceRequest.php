<?php

declare(strict_types=1);

namespace Gbairai\Core\Http\Requests;

use Gbairai\Core\Enums\SpaceType;
use Gbairai\Core\Models\Space; // Importer Space
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();
        /** @var Space|null $space */
        $space = $this->route('space');
        return $user !== null && $space !== null && $user->can('update', $space);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            // 'type' => ['sometimes', 'required', Rule::in(SpaceType::values())],
            'ticket_price' => [
                // Rule::requiredIf(fn () => $this->input('type', $this->route('space')?->type?->value) === SpaceType::PUBLIC_PAID->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'currency' => [
                // Rule::requiredIf(fn () => $this->input('type', $this->route('space')?->type?->value) === SpaceType::PUBLIC_PAID->value),
                'nullable',
                'string',
                'size:3',
            ],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'is_recording_enabled_by_host' => ['sometimes', 'boolean'],
            'scheduled_at' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                function ($attribute, $value, $fail) {
                    /** @var Space|null $space */
                    $space = $this->route('space');
                    if ($space && $space->status === \Gbairai\Core\Enums\SpaceStatus::LIVE) {
                        if ($this->input($attribute) !== $space->scheduled_at?->format('Y-m-d H:i:s')) {
                            $fail("La date de programmation ne peut pas être modifiée lorsqu'un Space est en direct.");
                        }
                    }
                    if ($value && strtotime($value) < time() && (!$space || $space->scheduled_at?->format('Y-m-d H:i:s') !== $value)) {
                         $fail('La date de programmation ne peut pas être dans le passé.');
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
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
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        if (array_key_exists('is_recording_enabled_by_host', $validated)) {
             $validated['is_recording_enabled_by_host'] = filter_var($validated['is_recording_enabled_by_host'], FILTER_VALIDATE_BOOLEAN);
        }
        return $validated;
    }
}
