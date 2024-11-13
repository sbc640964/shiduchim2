<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'schools';

    protected static ?string $label = 'מוסד';

    protected static ?string $navigationIcon = 'iconsax-bul-book-saved';

    protected static ?string $pluralLabel = 'מוסדות';

    //    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('שם המוסד')
                ->required(),

            Forms\Components\Select::make('city')
                ->label('עיר')
                ->searchable()
                ->relationship('city', 'name', fn ($query) => $query->orderBy('name'))
                ->createOptionForm(function (Form $form) {
                    return $form->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('שם העיר')
                            ->required(),
                        Forms\Components\TextInput::make('country')
                            ->label('מדינה')
                            ->required(),
                    ]);
                })
                ->createOptionAction(fn (Action $action) => $action->modalWidth('sm'))
                ->createOptionModalHeading('הוספת עיר')
                ->required(),

            Forms\Components\Select::make('gender')
                ->label('בנים/בנות')
                ->options([
                    'B' => 'בנים',
                    'G' => 'בנות',
                ])
                ->required(),

            Forms\Components\Select::make('type')
                ->label('סוג המוסד')
                ->native(false)
                ->options(fn (Forms\Get $get) => \Arr::only(
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
            Tables\Columns\TextInputColumn::make('name')
                ->label('שם המוסד')
                ->searchable()
                ->sortable(),

            Tables\Columns\SelectColumn::make('gender')
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

            Tables\Columns\SelectColumn::make('type')
                ->label('סוג המוסד')
                ->options(fn (School $school) => \Arr::only(
                    School::$typeLabel,
                    $school->gender === 'B'
                        ? ['YS', 'YB', 'YH', 'TT', 'SH']
                        : ['TT', 'BS', 'T', 'S']
                ))
                ->selectablePlaceholder(false)
                ->sortable(),

        ])
            ->actions([
                Tables\Actions\Action::make('contacts')
                    ->label('אנשי קשר')
                    ->iconButton()
                    ->color('gray')
                    ->url(fn ($record) => static::getUrl('contacts', ['record' => $record->id]))
                    ->icon('iconsax-bul-user-square'),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('sm')
                    ->icon('iconsax-bul-edit-2'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \app\Filament\Clusters\Settings\Resources\SchoolResource\Pages\ListSchools::route('/'),
            //            'create' => Pages\CreateSchool::route('/create'),
            //            'edit' => Pages\EditSchool::route('/{record}/edit'),
            'contacts' => \app\Filament\Clusters\Settings\Resources\SchoolResource\Pages\Contacts::route('/{record}/contacts'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            \app\Filament\Clusters\Settings\Resources\SchoolResource\Pages\Contacts::class,
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
