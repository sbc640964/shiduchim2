<?php

namespace App\Filament\Clusters\Settings\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use App\Filament\Clusters\Settings\Resources\CityResource\Pages\ListCities;
use App\Filament\Clusters\Settings\Resources\CityResource\Pages\CreateCity;
use App\Filament\Clusters\Settings;
use App\Models\City;
USE App\Filament\Clusters\Settings\Resources\CityResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $slug = 'cities';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'עיר';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-map-1';

    protected static ?string $pluralLabel = 'ערים';

    //    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('שם')
                ->required(),

            TextInput::make('country')
                ->label('מדינה')
                ->required(),

            TextInput::make('state')
                ->label('מחוז'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label('שם')
                ->searchable()
                ->sortable(),

            TextColumn::make('country')
                ->label('מדינה')
                ->sortable(),

            TextColumn::make('state')
                ->label('מחוז')
                ->sortable(),
        ])
            ->filters([
                SelectFilter::make('country')
                    ->label('מדינה')
                    ->options(City::select('country')->distinct()->pluck('country')->mapWithKeys(fn ($country) => [$country => $country])->filter()->toArray() ?? []),
            ])->recordActions([
                Action::make('join')
                    ->iconButton()
                    ->color('gray')
                    ->schema([
                        Select::make('cities')
                            ->label('ערים')
                            ->options(fn (City $city) => City::where('id', '!=', $city->id)->pluck('name', 'id')->toArray())
                            ->multiple()
                            ->required(),
                    ])
                    ->action(function (City $city, array $data) {
                        $city->mergeCities($data['cities'] ?? []);
                    })
                    ->icon('iconsax-two-recovery-convert'),
                DeleteAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton()
                    ->modalWidth('sm'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCities::route('/'),
            'create' => CreateCity::route('/create'),
            //            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
