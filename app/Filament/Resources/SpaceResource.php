<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SpaceResource\Pages;
use App\Filament\Resources\SpaceResource\RelationManagers;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Enums\SpaceStatus; // Importer les Enums
use Gbairai\Core\Enums\SpaceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope; // Si vous utilisez SoftDeletes
use App\Models\User; // Pour le select de l'hôte

// Importer les Actions du package Gbairai
use Gbairai\Core\Actions\Spaces\CreateSpaceAction;
use Gbairai\Core\Actions\Spaces\UpdateSpaceAction;
use Gbairai\Core\Actions\Spaces\DeleteSpaceAction;
use Gbairai\Core\Actions\Spaces\StartSpaceAction;
use Gbairai\Core\Actions\Spaces\EndSpaceAction;

// Importer les FormRequests du package Gbairai (pour les règles de validation si besoin)
use Gbairai\Core\Http\Requests\StoreSpaceRequest;
use Gbairai\Core\Http\Requests\UpdateSpaceRequest;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction; // Alias pour éviter conflit avec notre Action
use Filament\Tables\Actions\Action as TableAction; // Action de table générique
use Filament\Tables\Actions\ViewAction;


class SpaceResource extends Resource
{
    protected static ?string $model = Space::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone'; // Icône pour la navigation

    protected static ?string $recordTitleAttribute = 'title'; // Attribut utilisé pour le titre des enregistrements

    public static function form(Form $form): Form
    {
        // Récupérer les règles de validation depuis les FormRequests pour les réutiliser
        // C'est une façon de centraliser. Sinon, vous pouvez redéfinir les règles ici.
        $storeRules = (new StoreSpaceRequest())->rules();
        $updateRules = (new UpdateSpaceRequest())->rules(); // Peut être utilisé conditionnellement (sur $operation)

        return $form
            ->schema([
                Section::make('Informations Générales')
                    ->columns(2)
                    ->schema([
                        Select::make('host_user_id')
                            ->label('Hôte')
                            ->relationship('host', 'username') // Suppose que votre User model a un attribut 'username'
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages(['required' => 'L\'hôte est obligatoire.']) // Exemple de message personnalisé
                            ->rules($form->getOperation() === 'create' ? $storeRules['host_user_id'] ?? ['required'] : $updateRules['host_user_id'] ?? [])
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->rules($form->getOperation() === 'create' ? $storeRules['title'] : $updateRules['title'] ?? [])
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->maxLength(1000)
                            ->rules($form->getOperation() === 'create' ? $storeRules['description'] ?? [] : $updateRules['description'] ?? [])
                            ->columnSpanFull(),

                        TextInput::make('cover_image_url')
                            ->label('URL de l\'image de couverture')
                            ->nullable()
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Configuration du Space')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options(collect(SpaceType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                            ->required()
                            ->reactive() // Pour que les champs dépendants (ticket_price) réagissent
                            ->rules($form->getOperation() === 'create' ? $storeRules['type'] : $updateRules['type'] ?? []),

                        Select::make('status')
                            ->label('Statut')
                            ->options(collect(SpaceStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                            ->required()
                            // Le statut est souvent géré par des actions, donc le rendre readOnly ou le cacher en édition
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(false) // Ne pas envoyer cette valeur lors de la soumission du formulaire d'édition
                            ->visible(fn (string $operation): bool => $operation === 'create'), // Seulement visible à la création

                        TextInput::make('ticket_price')
                            ->label('Prix du Billet')
                            ->numeric()
                            ->prefix('XOF') // Ou un select pour la devise
                            ->minValue(0)
                            ->visible(fn (Forms\Get $get): bool => $get('type') === SpaceType::PUBLIC_PAID->value)
                            // ->requiredIf('type', SpaceType::PUBLIC_PAID->value) // Filament gère cela avec ->visible et la logique de validation
                            ->rules($form->getOperation() === 'create' ? $storeRules['ticket_price'] ?? [] : $updateRules['ticket_price'] ?? []),


                        // TextInput::make('currency') // Si vous voulez un champ séparé pour la devise
                        //     ->label('Devise')
                        //     ->maxLength(3)
                        //     ->visible(fn (Forms\Get $get): bool => $get('type') === SpaceType::PUBLIC_PAID->value)
                        //     ->rules($form->getOperation() === 'create' ? $storeRules['currency'] ?? [] : $updateRules['currency'] ?? []),


                        TextInput::make('max_participants')
                            ->label('Max Participants')
                            ->nullable()
                            ->integer()
                            ->minValue(1)
                            ->rules($form->getOperation() === 'create' ? $storeRules['max_participants'] ?? [] : $updateRules['max_participants'] ?? []),

                        Toggle::make('is_recording_enabled_by_host')
                            ->label('Activer l\'enregistrement (par l\'hôte)')
                            ->helperText('Seuls les hôtes premium peuvent enregistrer.')
                            ->rules($form->getOperation() === 'create' ? $storeRules['is_recording_enabled_by_host'] ?? [] : $updateRules['is_recording_enabled_by_host'] ?? []),
                    ]),

                Section::make('Programmation')
                    ->columns(1)
                    ->schema([
                        DateTimePicker::make('scheduled_at')
                            ->label('Programmé pour le')
                            ->nullable()
                            ->native(false) // Utiliser le picker JS par défaut de Filament
                            ->minDate(now()) // Ne pas programmer dans le passé
                            ->rules($form->getOperation() === 'create' ? $storeRules['scheduled_at'] ?? [] : $updateRules['scheduled_at'] ?? []),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('host.username') // Accéder à la relation
                    ->label('Hôte')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge() // Affiche l'enum avec un style de badge
                    ->formatStateUsing(fn (SpaceStatus $state): string => $state->label())
                    ->color(fn (SpaceStatus $state): string => match ($state) {
                        SpaceStatus::SCHEDULED => 'warning',
                        SpaceStatus::LIVE => 'success',
                        SpaceStatus::ENDED => 'gray',
                        SpaceStatus::CANCELLED => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (SpaceType $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->label('Programmé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Cacher par défaut, peut être affiché
                TextColumn::make('started_at')
                    ->label('Démarré le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ended_at')
                    ->label('Terminé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_recording_enabled_by_host')
                    ->label('Enregistrement')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(SpaceStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(SpaceType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                SelectFilter::make('host_user_id')
                    ->label('Hôte')
                    ->relationship('host', 'username')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([ // Actions pour chaque ligne
                ViewAction::make(),
                EditAction::make(),
                TableAction::make('start')
                    ->label('Démarrer')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Space $record, StartSpaceAction $startSpaceAction) {
                        /** @var \App\Models\User $user */
                        $user = auth()->user();
                        try {
                            $startSpaceAction->execute($user, $record);
                            \Filament\Notifications\Notification::make()
                                ->title('Space démarré')
                                ->body("Le Space '{$record->title}' a été démarré.")
                                ->success()
                                ->send();
                        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title('Non autorisé')->body("Vous n'êtes pas autorisé à démarrer ce Space.")->send();
                        } catch (\RuntimeException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                        }
                    })
                    ->visible(fn (Space $record): bool => $record->status === SpaceStatus::SCHEDULED && auth()->user()?->can('start', $record) ?? false),

                TableAction::make('end')
                    ->label('Terminer')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Space $record, EndSpaceAction $endSpaceAction) {
                        /** @var \App\Models\User $user */
                        $user = auth()->user();
                        try {
                            $endSpaceAction->execute($user, $record);
                            \Filament\Notifications\Notification::make()->success()->title('Space terminé')->body("Le Space '{$record->title}' est maintenant terminé.")->send();
                        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title('Non autorisé')->body("Vous n'êtes pas autorisé à terminer ce Space.")->send();
                        } catch (\RuntimeException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title('Erreur')->body($e->getMessage())->send();
                        }
                    })
                    ->visible(fn (Space $record): bool => $record->status === SpaceStatus::LIVE && auth()->user()?->can('end', $record) ?? false),

                TableDeleteAction::make() // Utilise la policy 'delete' par défaut
                    ->action(function (Space $record, DeleteSpaceAction $deleteSpaceAction) {
                         /** @var \App\Models\User $user */
                        $user = auth()->user();
                        try {
                            $deleteSpaceAction->execute($user, $record);
                            \Filament\Notifications\Notification::make()->success()->title('Space supprimé')->send();
                        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                            \Filament\Notifications\Notification::make()->danger()->title('Non autorisé')->body("Vous n'êtes pas autorisé à supprimer ce Space.")->send();
                        } catch (\RuntimeException $e) { // Ou toute autre exception métier que votre action pourrait lever
                            \Filament\Notifications\Notification::make()->danger()->title('Erreur de suppression')->body($e->getMessage())->send();
                        }
                    }),
            ])
            ->bulkActions([ // Actions sur plusieurs lignes sélectionnées
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, DeleteSpaceAction $deleteSpaceAction) {
                            /** @var \App\Models\User $user */
                            $user = auth()->user();
                            $records->each(function (Space $record) use ($user, $deleteSpaceAction) {
                                if ($user->can('delete', $record)) {
                                    try {
                                        $deleteSpaceAction->execute($user, $record);
                                    } catch (\Throwable $e) {
                                        // Gérer l'erreur pour ce record spécifique, peut-être notifier
                                    }
                                }
                            });
                            \Filament\Notifications\Notification::make()->success()->title('Spaces sélectionnés supprimés (si autorisé).')->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ParticipantsRelationManager::class, // Ajoutez cette ligne
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpaces::route('/'),
            'create' => Pages\CreateSpace::route('/create'),
            'edit' => Pages\EditSpace::route('/{record}/edit'),
            //'view' => Pages\ViewSpace::route('/{record}'), // Si vous avez une page de vue dédiée
        ];
    }

    /**
     * Surcharger pour utiliser nos propres actions pour la création.
     * Filament appelle `resolveRecord()` pour obtenir le modèle sur les pages d'édition/vue.
     * Pour la création et la sauvegarde, nous allons surcharger les méthodes dans les Pages correspondantes.
     */
}