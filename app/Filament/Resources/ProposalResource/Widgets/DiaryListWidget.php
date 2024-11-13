<?php

namespace App\Filament\Resources\ProposalResource\Widgets;

use App\Filament\Resources\ProposalResource\Pages\Diaries;
use App\Filament\Resources\ProposalResource\Traits\DiariesComponents;
use App\Models\Diary;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class DiaryListWidget extends BaseWidget
{
    use DiariesComponents,
        InteractsWithRecord {
            table as protected tableComponent;
        }

    protected int|string|array $columnSpan = 2;

    public function table(Tables\Table $table): Tables\Table
    {
        return $this->tableComponent($table)
            ->heading('יומן פעילות')
            ->headerActions([
                Tables\Actions\Action::make('create-diary')
                    ->label('הוסף תיעוד')
                    ->model(Diary::class)
                    ->action(fn ($data) => Diaries::createNewDiary($data, $this->getRecord(), $data['side'] ?? null))
                    ->form(fn ($form) => $this->form($form)),
            ])
            ->paginationPageOptions([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->relationship(fn (): Relation|Builder => $this->getRelationship())
            ->recordAction(function (Model $record, Table $table): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $table->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action->record($record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    if ($action->getUrl()) {
                        continue;
                    }

                    return $action->getName();
                }

                return null;
            })
            ->recordUrl(function (Model $record, Table $table): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $table->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action->record($record);

                    if ($action->isHidden()) {
                        continue;
                    }

                    $url = $action->getUrl();

                    if (! $url) {
                        continue;
                    }

                    return $url;
                }

                return null;
            });

    }

    //    public function getRecord() {
    //
    //    }

    private function getRelationship()
    {
        return $this->getRecord()->diaries();
    }

    public function getOwnerRecord()
    {
        return $this->getRecord();
    }
}
