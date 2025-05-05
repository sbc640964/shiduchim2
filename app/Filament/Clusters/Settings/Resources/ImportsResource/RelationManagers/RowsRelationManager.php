<?php

namespace App\Filament\Clusters\Settings\Resources\ImportsResource\RelationManagers;

use App\Helpers\LivewireDotStateFix;
use App\Models\ImportRow;
use App\Services\Imports\Students\Importer;
use Arr;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class RowsRelationManager extends RelationManager
{
    protected static string $relationship = 'rows';

    #[Url]
    public ?string $activeTab = null;
    public function isReadOnly(): bool
    {
        return false;
    }
    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\KeyValue::make('data')
                    ->dehydrateStateUsing(fn (?array $state) => LivewireDotStateFix::fix($state))
                    ->label('נתונים')
                    ->deleteAction(fn ($action) => $action->icon('heroicon-o-trash'))
            ]);
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return match ($this->getOwnerRecord()->status) {
            'pending' => 'pending',
            'error' => 'failed',
            'finished' => 'success',
            default => 'all',
        };
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('הכל'),
            'pending' => Tab::make('ממתין')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereStatus('pending')),
            'success' => Tab::make('עבר בהצלחה')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereStatus('success')),
            'failed' => Tab::make('נכשל')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereStatus('failed')),
        ];
    }

    public function getColumns()
    {
        $columnsOptions = match ($this->getOwnerRecord()->type) {
            'students' => Importer::fields(),
            default => [],
        };

        $mapping = Arr::only($this->getOwnerRecord()->options['mapping'], array_column($columnsOptions, 'name'));

        $columnsOptions = array_filter($columnsOptions, fn ($column) => isset($mapping[$column['name']]));

        $columns = [];

        foreach ($columnsOptions as $column) {
            $columns[] = Tables\Columns\TextColumn::make($column['name'])
                ->label($column['label'])
                ->searchable(['data->'.$mapping[$column['name']]])
                ->sortable(['data->'.$mapping[$column['name']]])
                ->state(fn ($record) => $record?->data[$mapping[$column['name']]] ?? null);
        }

        return $columns;

    }
    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('רשומת ייבוא')
            ->heading('רשומות')
            ->recordTitleAttribute('id')
            ->columns(array_merge(
                $this->getColumns(),
                [
                    Tables\Columns\TextColumn::make('status')
                        ->label('סטטוס')
                        ->searchable()
                        ->sortable()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'pending' => 'ממתין',
                            'success' => 'הצלחה',
                            'failed' => 'נכשל',
                            default => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'pending' => 'gray',
                            'success' => 'success',
                            'failed' => 'danger',
                            default => 'gray',
                        })
                        ->badge(),

                    Tables\Columns\TextColumn::make('error')
                        ->label('שגיאה')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('import_model_state')
                        ->label('סטטוס ייבוא')
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'updated' => 'עדכון',
                            'created' => 'יצירה',
                            default => $state,
                        })
                        ->label('סיום')
                        ->searchable()
                        ->sortable(),
                ]
            ))
            ->filters([
                //
            ])
            ->headerActions([
//                Tables\Actions\CreateAction::make()
//                    ->label('הוסף רשומת יבוא')
//                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\Action::make('run')
                    ->label('הפעל')
                    ->iconButton()
                    ->icon(fn(ImportRow $record) =>  $record->status === 'pending' ? 'heroicon-o-play': 'heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->hidden(fn (ImportRow $record) => ! in_array($record->status, ['pending', 'failed']))
                    ->action(fn (ImportRow $record) => $record->run($record->status !== 'failed')),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-pencil')
                    ->slideOver(),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
