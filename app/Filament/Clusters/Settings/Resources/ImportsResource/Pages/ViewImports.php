<?php

namespace App\Filament\Clusters\Settings\Resources\ImportsResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Clusters\Settings\Resources\ImportsResource\Widgets\ImportStates;
use App\Filament\Clusters\Settings\Resources\ImportsResource;
use App\Models\ImportBatch;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewImports extends ViewRecord
{
    protected static string $resource = ImportsResource::class;

    public function getRecord(): Model|ImportBatch
    {
        return parent::getRecord();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('run')
                ->label('הפעל')
                ->requiresConfirmation()
                ->visible(fn () => $this->getRecord()->allowRun() ||  $this->getRecord()->allowRerun())
                ->modalHeading('האם אתה בטוח שברצונך להפעיל את הייבוא?' . ($this->getRecord()->allowRerun() ? ' **שוב**' : ''))
                ->modalDescription('הפעולה תפעיל את הייבוא ותתחיל בעיבוד הנתונים ברקע.')
                ->action(function () {
                    $this->getRecord()->allowRun()
                        ? $this->getRecord()->run()
                        : $this->getRecord()->rerun();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ImportStates::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }
}
