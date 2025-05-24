<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpaceResource\Pages;

use App\Filament\Resources\SpaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Gbairai\Core\Actions\Spaces\UpdateSpaceAction; // Importer
// use Gbairai\Core\Http\Requests\UpdateSpaceRequest; // Si utilisé pour validation

class EditSpace extends EditRecord
{
    protected static string $resource = SpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make() // Utilise la policy et notre DeleteSpaceAction si surchargé dans la ressource
             ->action(function (\Gbairai\Core\Models\Space $record) {
                 /** @var \App\Models\User $user */
                 $user = auth()->user();
                 $deleteAction = app(\Gbairai\Core\Actions\Spaces\DeleteSpaceAction::class);
                 try {
                     $deleteAction->execute($user, $record);
                     \Filament\Notifications\Notification::make()->success()->title('Space supprimé')->send();
                     // Rediriger après suppression
                     $this->redirect($this->getResource()::getUrl('index'));
                 } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                     \Filament\Notifications\Notification::make()->danger()->title('Non autorisé')->body("Vous n'êtes pas autorisé à supprimer ce Space.")->send();
                 } catch (\RuntimeException $e) {
                     \Filament\Notifications\Notification::make()->danger()->title('Erreur de suppression')->body($e->getMessage())->send();
                 }
             }),
        ];
    }

    /**
     * Surcharger pour préparer les données pour notre UpdateSpaceAction.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Transformations similaires à CreateSpace, si nécessaire
        if (isset($data['type']) && is_string($data['type'])) {
            $data['type'] = \Gbairai\Core\Enums\SpaceType::from($data['type']);
        }
        if (array_key_exists('is_recording_enabled_by_host', $data)) {
             $data['is_recording_enabled_by_host'] = filter_var($data['is_recording_enabled_by_host'], FILTER_VALIDATE_BOOLEAN);
        }
        // Le statut ne devrait pas être modifiable via ce formulaire
        unset($data['status']);

        return $data;
    }

    /**
     * Surcharger pour utiliser notre UpdateSpaceAction.
     *
     * @param \Illuminate\Database\Eloquent\Model $record Le Space à mettre à jour.
     * @param array<string, mixed> $data Les données du formulaire, déjà passées par mutateFormDataBeforeSave.
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var \Gbairai\Core\Models\Space $spaceRecord */
        $spaceRecord = $record; // Pour le typage

        $updateSpaceAction = app(UpdateSpaceAction::class);
        return $updateSpaceAction->execute($user, $spaceRecord, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Space mis à jour avec succès';
    }
}