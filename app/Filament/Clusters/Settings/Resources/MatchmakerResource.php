<?php

namespace App\Filament\Clusters\Settings\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Settings\Resources\MatchmakerResource\Pages\ListMatchmakers;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\MatchmakerResource\Pages;
use App\Models\Matchmaker;
use App\Models\Person;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MatchmakerResource extends Resource
{
    protected static ?string $model = Matchmaker::class;

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-music-play';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'שדכן';

    protected static ?string $pluralLabel = 'שדכנים';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Hidden::make('created_by')
                    ->default(auth()->id())
                    ->required(),
                Select::make('person_id')
                    ->label('אדם')
                    ->disabledOn('edit')
                    ->relationship('person', 'full_name', fn ($query) => $query->with('father', 'fatherInLaw', 'spouse')->limit(50))
                    ->getOptionLabelFromRecordUsing(fn (Person $person) => $person->select_option_html_with_pivot_side)
                    ->getSearchResultsUsing(fn ($search) => Person::searchName($search)->with('father', 'fatherInLaw', 'spouse')->get()->pluck('select_option_html_with_pivot_side', 'id')->toArray())
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
                IconColumn::make('active')
                    ->label('')
                    ->width('40px'),
                TextColumn::make('person.full_name')
                    ->label('שם מלא')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('טלפון')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('רמה')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->tooltip('עריכה')
                    ->icon('iconsax-bul-edit-2')
                    ->iconButton()
                    ->modalWidth('sm'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListMatchmakers::route('/'),
            //            'create' => Pages\CreateMatchmaker::route('/create'),
            //            'edit' => Pages\EditMatchmaker::route('/{record}/edit'),
        ];
    }
}
