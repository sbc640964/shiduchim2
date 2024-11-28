<?php

namespace App\Filament\Clusters\Settings\Resources\ImportsResource\Pages;

use App\Filament\Clusters\Settings\Resources\ImportsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewImports extends ViewRecord
{
    protected static string $resource = ImportsResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('run')
                ->label('הפעל')
                ->requiresConfirmation()
                ->hidden(fn () => $this->getRecord()->status !== 'pending')
                ->modalHeading('האם אתה בטוח שברצונך להפעיל את הייבוא?')
                ->modalDescription('הפעולה תפעיל את הייבוא ותתחיל בעיבוד הנתונים ברקע.')
                ->action(fn () => $this->getRecord()->run()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ImportsResource\Widgets\ImportStates::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }
}