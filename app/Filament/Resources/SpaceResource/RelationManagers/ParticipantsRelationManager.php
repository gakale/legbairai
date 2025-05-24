<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpaceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Gbairai\Core\Enums\SpaceParticipantRole; // Importer l'Enum
use Gbairai\Core\Models\SpaceParticipant; // Importer le modèle
use App\Models\User; // Importer le modèle User pour le select

// Importer nos Actions
use Gbairai\Core\Actions\Participants\ChangeParticipantRoleAction;
use Gbairai\Core\Actions\Participants\LeaveSpaceAction; // ou une KickParticipantAction

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Notifications\Notification;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    // Optionnel: Titre personnalisé pour le manager de relation
    protected static ?string $title = 'Participants du Space';

    protected static ?string $recordTitleAttribute = 'user.username'; // Pour afficher le nom d'utilisateur

    public function form(Form $form): Form
    {
        // Le formulaire ici est pour *modifier* un participant existant via le panneau admin,
        // ou pour *ajouter manuellement* un participant (moins courant pour le flux utilisateur normal).
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'username') // Relation vers le modèle User, affiche 'username'
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (string $operation) => $operation === 'edit') // Ne pas changer l'utilisateur en édition
                    ->createOptionForm([ // Permet de créer un User à la volée (si pertinent pour l'admin)
                        Forms\Components\TextInput::make('name')->required(), // Si User a un champ 'name'
                        Forms\Components\TextInput::make('username')->required()->unique(table: User::class, column: 'username'),
                        Forms\Components\TextInput::make('email')->email()->required()->unique(table: User::class, column: 'email'),
                        Forms\Components\TextInput::make('password')->password()->required()->minLength(8),
                    ])
                    ->validationMessages(['required' => 'L\'utilisateur est obligatoire.']),

                Select::make('role')
                    ->label('Rôle')
                    ->options(collect(SpaceParticipantRole::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->required()
                    ->validationMessages(['required' => 'Le rôle est obligatoire.']),

                Toggle::make('is_muted_by_host')
                    ->label('Muté par l\'hôte'),
         Toggle::make('is_self_muted')
                    ->label('Auto-muté')
                    ->disabled(), // L'auto-mute est généralement géré par l'utilisateur lui-même

                Toggle::make('has_raised_hand')
                    ->label('Main levée')
                    ->disabled(), // La main levée est aussi une action de l'utilisateur
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('user.username') // Alternative à la propriété statique
            ->columns([
                TextColumn::make('user.username') // Assurez-vous que votre modèle User a 'username'
                    ->label('Nom d\'utilisateur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (SpaceParticipantRole $state): string => $state->label())
                    ->color(fn (SpaceParticipantRole $state): string => match ($state) {
                        SpaceParticipantRole::LISTENER => 'gray',
                        SpaceParticipantRole::SPEAKER => 'info',
                        SpaceParticipantRole::CO_HOST => 'success',
                    })
                    ->sortable(),
                IconColumn::make('is_muted_by_host')
                    ->label('Muté (Hôte)')
                    ->boolean(),
                IconColumn::make('is_self_muted')
                    ->label('Auto-muté')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_raised_hand')
                    ->label('Main levée')
                    ->boolean()
                    ->trueIcon('heroicon-s-hand-raised') // Icône pour main levée
                    ->falseIcon('heroicon-o-hand-raised'), // Icône pour main non levée (ou vide)
                TextColumn::make('joined_at')
                    ->label('Rejoint le')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('left_at')
                    ->label('Quitté le')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('N/A'), // Si null
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(collect(SpaceParticipantRole::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            ])
            ->headerActions([ // Actions en haut de la table du manager
                Tables\Actions\CreateAction::make() // Pour ajouter manuellement un participant
                    ->mutateFormDataUsing(function (array $data): array {
                        // Assurer les valeurs par défaut si non fournies par le formulaire minimal
                        $data['is_muted_by_host'] = $data['is_muted_by_host'] ?? true;
                        $data['is_self_muted'] = $data['is_self_muted'] ?? true;
                        $data['has_raised_hand'] = $data['has_raised_hand'] ?? false;
                        $data['joined_at'] = $data['joined_at'] ?? now();
                        return $data;
                    })
                    // Ici, on n'appelle pas JoinSpaceAction car c'est une création manuelle par l'admin.
                    // JoinSpaceAction a des logiques (comme la vérification de max_participants)
                    // que l'admin pourrait vouloir outrepasser.
                    // La validation des données se fait par le form() ci-dessus.
            ])
            ->actions([ // Actions pour chaque ligne de participant
                EditAction::make(), // Ouvre le formulaire défini dans form() pour modifier
                TableDeleteAction::make('kick') // Renommer pour clarifier que c'est un "kick"
                    ->label('Retirer')
                    ->requiresConfirmation()
                    ->action(function (SpaceParticipant $record, LeaveSpaceAction $leaveSpaceAction) {
                        /** @var \App\Models\User $actor */
                        $actor = auth()->user(); // L'admin qui fait l'action
                        $space = $this->getOwnerRecord(); // Récupère le Space parent

                        // On ne peut pas retirer l'hôte principal du Space
                        if ($space->host_user_id === $record->user_id) {
                            Notification::make()->danger()->title('Action Interdite')->body("Vous ne pouvez pas retirer l'hôte principal du Space.")->send();
                            return;
                        }

                        // TODO: Ajouter une Policy SpaceParticipantPolicy@delete ou SpacePolicy@kickParticipant
                        // Pour l'instant, on suppose que l'admin peut le faire si ce n'est pas l'hôte.
                        // Gate::forUser($actor)->authorize('kick', $record);

                        try {
                            // LeaveSpaceAction attend UserContract (l'utilisateur qui part) et Space
                            // Ici, l'admin agit *sur* un participant.
                            // Nous simulons le départ du participant.
                            $leaveSpaceAction->execute($record->user, $space);
                            Notification::make()->success()->title('Participant retiré')->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                        }
                    }),

                // Action pour "Donner la parole"
                TableAction::make('grantSpeakingRights')
                    ->label('Donner la Parole')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (SpaceParticipant $record, ChangeParticipantRoleAction $changeRoleAction) {
                        /** @var \App\Models\User $actor */
                        $actor = auth()->user();
                        $space = $this->getOwnerRecord();
                        $targetUser = $record->user;

                        try {
                            // On change le rôle en SPEAKER. ChangeParticipantRoleAction s'occupera
                            // de mettre has_raised_hand à false et potentiellement de démuter.
                            $changeRoleAction->execute($actor, $space, $targetUser, SpaceParticipantRole::SPEAKER);
                            Notification::make()->success()->title('Parole accordée')->body("L'utilisateur {$targetUser->username} peut maintenant parler.")->send();
                        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                            Notification::make()->danger()->title('Non autorisé')->body("Vous n'êtes pas autorisé à effectuer cette action.")->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                        }
                    })
                    ->visible(fn (SpaceParticipant $record): bool =>
                        ($record->role === SpaceParticipantRole::LISTENER && $record->has_raised_hand) &&
                        (auth()->user()?->can('manageParticipants', $this->getOwnerRecord()) ?? false)
                    ),

                // Action pour "Reprendre la parole" (remettre en auditeur)
                TableAction::make('revokeSpeakingRights')
                    ->label('Reprendre la Parole')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (SpaceParticipant $record, ChangeParticipantRoleAction $changeRoleAction) {
                        /** @var \App\Models\User $actor */
                        $actor = auth()->user();
                        $space = $this->getOwnerRecord();
                        $targetUser = $record->user;

                        try {
                            // On change le rôle en LISTENER. ChangeParticipantRoleAction s'occupera
                            // de potentiellement muter l'utilisateur.
                            $changeRoleAction->execute($actor, $space, $targetUser, SpaceParticipantRole::LISTENER);
                            Notification::make()->success()->title('Parole reprise')->body("L'utilisateur {$targetUser->username} est maintenant auditeur.")->send();
                        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                            Notification::make()->danger()->title('Non autorisé')->body("Vous n'êtes pas autorisé à effectuer cette action.")->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                        }
                    })
                    ->visible(fn (SpaceParticipant $record): bool =>
                        ($record->role === SpaceParticipantRole::SPEAKER) &&
                        (auth()->user()?->can('manageParticipants', $this->getOwnerRecord()) ?? false)
                    ),

                // Action pour changer le rôle
                TableAction::make('changeRole')
                    ->label('Changer Rôle')
                    ->icon('heroicon-o-user-group')
                    ->form([ // Petit formulaire dans un modal pour le nouveau rôle
                        Select::make('new_role')
                            ->label('Nouveau Rôle')
                            ->options(collect(SpaceParticipantRole::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                            ->required(),
                    ])
                    ->action(function (SpaceParticipant $record, array $data, ChangeParticipantRoleAction $changeRoleAction) {
                        /** @var \App\Models\User $actor */
                        $actor = auth()->user();
                        $space = $this->getOwnerRecord(); // Le Space parent
                        $targetUser = $record->user; // L'utilisateur dont le rôle change
                        $newRole = SpaceParticipantRole::from($data['new_role']);

                        try {
                            $changeRoleAction->execute($actor, $space, $targetUser, $newRole);
                            Notification::make()->success()->title('Rôle modifié')->send();
                        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                            Notification::make()->danger()->title('Non autorisé')->body("Vous n'êtes pas autorisé à modifier ce rôle.")->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                        }
                    })
                    ->visible(fn (SpaceParticipant $record): bool => auth()->user()?->can('manageParticipants', $this->getOwnerRecord()) ?? false),

            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(), // Pourrait kicker plusieurs utilisateurs
                // ]),
            ])
            // Par défaut, ne montre que les participants actifs
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('left_at'))
            ;
    }

    // Optionnel: Permet de créer un nouveau participant directement
    // protected function canCreate(): bool
    // {
    //     // Vérifier si l'utilisateur peut gérer les participants du Space parent
    //     return auth()->user()?->can('manageParticipants', $this->getOwnerRecord()) ?? false;
    // }

    // Optionnel: Permet d'attacher des utilisateurs existants qui ne sont pas encore participants
    // protected function canAttach(): bool
    // {
    //     return auth()->user()?->can('manageParticipants', $this->getOwnerRecord()) ?? false;
    // }
}