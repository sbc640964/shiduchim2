<?php

namespace App\Filament\Resources\PersonResource\RelationManagers;

use App\Models\Old\Person;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RelativesMatherRelationManager extends RelationManager
{
    protected static string $relationship = 'relatives';

    protected static ?string $label = 'קרוב משפחה';

    protected static ?string $pluralLabel = 'קרובי משפחה';

    protected static ?string $title = 'צד אם';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make([
                    Forms\Components\TextInput::make('relation')
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
                Tables\Columns\TextColumn::make('ichud_id')
                    ->label('מזהה')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('שם')
                    ->extraAttributes(['class' => 'font-medium'])
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['first_name', 'last_name']),

                Tables\Columns\TextColumn::make('pivot.relation')
                    ->label('קירוב')
                    ->formatStateUsing(function (Person $record) {
                        return Person::RELATION_TYPES[$record->pivot->relation]
                            ?? $record->pivot->relation;
                    }),

                Tables\Columns\TextColumn::make('father_name')
                    ->label('שם האב')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('father_in_law_name')
                    ->label('שם חותן')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //relation types
                Tables\Filters\SelectFilter::make('relation')
                    ->label('קירוב')
                    ->multiple()
                    ->options(Person::RELATION_TYPES),
            ])
            ->headerActions([
                //                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                //                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
