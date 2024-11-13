<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\MatchmakerResource\Pages;
use App\Models\Matchmaker;
use App\Models\Person;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MatchmakerResource extends Resource
{
    protected static ?string $model = Matchmaker::class;

    protected static ?string $navigationIcon = 'iconsax-bul-music-play';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'שדכן';

    protected static ?string $pluralLabel = 'שדכנים';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Hidden::make('created_by')
                    ->default(auth()->id())
                    ->required(),
                Select::make('person_id')
                    ->label('אדם')
                    ->disabledOn('edit')
                    ->relationship('person', 'full_name', fn ($query) => $query->with('father')->limit(50))
                    ->getOptionLabelFromRecordUsing(fn (Person $person) => $person->select_option_html)
                    ->getSearchResultsUsing(fn ($search) => Person::searchName($search)->with('father')->get()->pluck('select_option_html', 'id')->toArray())
                    ->searchable()
                    ->allowHtml()
                    ->required(),
                Select::make('level')
                    ->label('רמה')
                    ->options([
                        '1' => 'נמוכה',
                        '2' => 'בינונית',
                        '3' => 'גבוהה',
                    ])
                    ->default(2)
                    ->required(),
                Checkbox::make('active')
                    ->label('פעיל')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('active')
                    ->label('')
                    ->width('40px'),
                Tables\Columns\TextColumn::make('person.full_name')
                    ->label('שם מלא')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('טלפון')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('רמה')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('עריכה')
                    ->icon('iconsax-bul-edit-2')
                    ->iconButton()
                    ->modalWidth('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMatchmakers::route('/'),
            //            'create' => Pages\CreateMatchmaker::route('/create'),
            //            'edit' => Pages\EditMatchmaker::route('/{record}/edit'),
        ];
    }
}
