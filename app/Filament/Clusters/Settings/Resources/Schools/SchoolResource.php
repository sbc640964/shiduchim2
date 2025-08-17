<?php

namespace App\Filament\Clusters\Settings\Resources\Schools;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Arr;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Actions\EditAction;
use App\Filament\Clusters\Settings\Resources\Schools\Pages\ListSchools;
use App\Filament\Clusters\Settings\Resources\Schools\Pages\Contacts;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\School;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'schools';

    protected static ?string $label = 'מוסד';

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-book-saved';

    protected static ?string $pluralLabel = 'מוסדות';

    //    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('שם המוסד')
                ->required(),

            Select::make('city')
                ->label('עיר')
                ->searchable()
                ->relationship('city', 'name', fn ($query) => $query->orderBy('name'))
                ->createOptionForm(function (Schema $schema) {
                    return $schema->components([
                        TextInput::make('name')
                            ->label('שם העיר')
                            ->required(),
                        TextInput::make('country')
                            ->label('מדינה')
                            ->required(),
                    ]);
                })
                ->createOptionAction(fn (Action $action) => $action->modalWidth('sm'))
                ->createOptionModalHeading('הוספת עיר')
                ->required(),

            Select::make('gender')
                ->label('בנים/בנות')
                ->options([
                    'B' => 'בנים',
                    'G' => 'בנות',
                ])
                ->required(),

            Select::make('type')
                ->label('סוג המוסד')
                ->native(false)
                ->options(fn (Get $get) => Arr::only(
                    School::$typeLabel,
                    $get('gender') === 'B'
                        ? ['YS', 'YB', 'YH', 'TT', 'SH']
                        : ['TT', 'BS', 'T', 'S']
                ))
                ->required(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextInputColumn::make('name')
                ->label('שם המוסד')
                ->searchable()
                ->sortable(),

            SelectColumn::make('gender')
                ->label('בנים/בנות')
                ->options([
                    'B' => 'בנים',
                    'G' => 'בנות',
                ])
                ->searchable()
                ->sortable(),

            TextColumn::make('city.name')
                ->label('עיר')
                ->searchable()
                ->sortable(),

            SelectColumn::make('type')
                ->label('סוג המוסד')
                ->options(fn (School $school) => Arr::only(
                    School::$typeLabel,
                    $school->gender === 'B'
                        ? ['YS', 'YB', 'YH', 'TT', 'SH']
                        : ['TT', 'BS', 'T', 'S']
                ))
                ->selectablePlaceholder(false)
                ->sortable(),

        ])
            ->recordActions([
                Action::make('contacts')
                    ->label('אנשי קשר')
                    ->iconButton()
                    ->color('gray')
                    ->url(fn ($record) => static::getUrl('contacts', ['record' => $record->id]))
                    ->icon('iconsax-bul-user-square'),
                EditAction::make()
                    ->iconButton()
                    ->modalWidth('sm')
                    ->icon('iconsax-bul-edit-2'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchools::route('/'),
            //            'create' => Pages\CreateSchool::route('/create'),
            //            'edit' => Pages\EditSchool::route('/{record}/edit'),
            'contacts' => Contacts::route('/{record}/contacts'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Contacts::class,
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
