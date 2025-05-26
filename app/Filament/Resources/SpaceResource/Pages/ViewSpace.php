<?php

namespace App\Filament\Resources\SpaceResource\Pages;

use App\Filament\Resources\SpaceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewSpace extends ViewRecord
{
    protected static string $resource = SpaceResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Modifier')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
