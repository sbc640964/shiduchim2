<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use App\Models\Old\Person;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RelativesNuclearRelationManager extends RelationManager
{
    protected static string $relationship = 'relatives';

    protected static ?string $label = 'קרוב משפחה';

    protected static ?string $pluralLabel = 'קרובי משפחה';

    protected static ?string $title = 'משפחה גריעינית';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    TextInput::make('relation')
                        ->label('קירוב')
                        ->required()
                        ->autofocus()
                        ->placeholder('אב'),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('ichud_id')
                    ->label('מזהה')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('שם')
                    ->extraAttributes(['class' => 'font-medium'])
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['first_name', 'last_name']),

                TextColumn::make('pivot.relation')
                    ->label('קירוב')
                    ->formatStateUsing(function (Person $record) {
                        return Person::RELATION_TYPES[$record->pivot->relation]
                            ?? $record->pivot->relation;
                    }),

                TextColumn::make('father_name')
                    ->label('שם האב')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('father_in_law_name')
                    ->label('שם חותן')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //relation types
                SelectFilter::make('relation')
                    ->label('קירוב')
                    ->multiple()
                    ->options(Person::RELATION_TYPES),
            ])
            ->headerActions([
                //                Tables\Actions\CreateAction::make(),
                AttachAction::make(),
            ])
            ->recordActions([
                //                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
