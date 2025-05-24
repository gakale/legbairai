<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpaceResource\Pages;

use App\Filament\Resources\SpaceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Gbairai\Core\Actions\Spaces\CreateSpaceAction; // Importer
use Gbairai\Core\Http\Requests\StoreSpaceRequest; // Importer pour les données validées
use App\Models\User;
class CreateSpace extends CreateRecord
{
    protected static string $resource = SpaceResource::class;

    /**
     * Surcharger pour utiliser notre FormRequest pour la validation
     * et préparer les données pour notre Action.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['type']) && is_string($data['type'])) {
            $data['type'] = \Gbairai\Core\Enums\SpaceType::from($data['type']);
        }
        $data['is_recording_enabled_by_host'] = filter_var($data['is_recording_enabled_by_host'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // S'assurer que host_user_id est présent si le champ est requis
        if (empty($data['host_user_id'])) {
            // Gérer le cas où host_user_id est manquant.
            // Soit le rendre explicitement obligatoire dans le formulaire Filament,
            // soit lever une exception, soit assigner un utilisateur par défaut si pertinent.
            // Pour l'instant, on suppose qu'il vient du formulaire.
        }

        return $data;
    }


    /**
     * Surcharger pour utiliser notre CreateSpaceAction.
     *
     * @param array<string, mixed> $data Les données du formulaire, déjà passées par mutateFormDataBeforeCreate
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \App\Models\User $userPerformingAction */ // Utilisateur admin effectuant la création
        $userPerformingAction = auth()->user();

        // Récupérer l'instance de l'hôte à partir de son ID
        // $data['host_user_id'] devrait contenir l'UUID de l'utilisateur sélectionné comme hôte
        if (empty($data['host_user_id'])) {
             // Vous devriez probablement lever une exception ici ou gérer via la validation Filament
             // que host_user_id est requis.
             throw new \InvalidArgumentException('Host user ID is required.');
        }
        /** @var \App\Models\User $host */
        $host = User::findOrFail($data['host_user_id']); // Maintenant, User est correctement résolu

        $createSpaceAction = app(CreateSpaceAction::class);

        return $createSpaceAction->execute($host, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Space créé avec succès';
    }
}