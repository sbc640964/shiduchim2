<?php

namespace App\Filament\Clusters\Settings\Resources\Forms;

use App\Filament\Clusters\Settings\Resources\Forms\Pages\CreateForm;
use App\Filament\Clusters\Settings\Resources\Forms\Pages\EditForm;
use App\Filament\Clusters\Settings\Resources\Forms\Pages\ListForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Collection;
use Cache;
use Blade;
use App;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Form;
use BladeUI\Icons\Factory as IconFactory;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use File;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-layer';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $label = 'טופס';

    protected static ?string $pluralLabel = 'טפסים';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('')
                    ->tabs([
                        Tab::make('general')
                            ->label('פרטים כלליים')
                            ->schema([
                                TextInput::make('name')
                                    ->label('שם')
                                    ->required()
                                    ->placeholder('הכנס את שם הטופס'),
                                Select::make('resource')
                                    ->native(false)
                                    ->options([
                                        'people' => 'אנשים',
                                        'students' => 'תלמידים',
                                        'proposals' => 'הצעות',
                                    ])
                                    ->label('משאב')
                                    ->required(),
                                Group::make([
                                    Checkbox::make('is_active')
                                        ->default(true)
                                        ->label('פעיל'),
                                    Checkbox::make('is_multiple')
                                        ->default(false)
                                        ->label('מרובה רשומות'),
                                ])->columns(2),
                            ]),

                        Tab::make('fields')
                            ->label('שדות')
                            ->schema([
                                Repeater::make('fields')
                                    ->hiddenLabel()
                                    ->collapsed()
                                    ->deleteAction(fn (Action $action) => $action->requiresConfirmation()
                                        ->icon('iconsax-bul-trash')
                                        ->modalHeading('מחיקת שדה')
                                    )
                                    ->addActionLabel('הוסף שדה')
                                    ->collapsible()
                                    ->itemLabel(fn ($state) => $state['label'] ?? null)
                                    ->columns(2)
                                    ->schema([
                                        Select::make('type')
                                            ->options([
                                                'text' => 'טקסט',
                                                'textarea' => 'טקסט ארוך',
                                                'number' => 'מספר',
                                                'date' => 'תאריך',
                                                'datetime' => 'תאריך ושעה',
                                                'select' => 'בחירה',
                                                'checkbox' => 'צ׳קבוקס',
                                                'radio' => 'רדיו',
                                            ])
                                            ->live()
                                            ->label('סוג')
                                            ->required(),
                                        TextInput::make('label')
                                            ->label('תווית')
                                            ->live()
                                            ->required(),
                                        TextInput::make('placeholder')
                                            ->label('שומר מקום'),
                                        Checkbox::make('required')
                                            ->label('חובה'),
                                        Textarea::make('help')
                                            ->label('טקסט עזרה')
                                            ->columnSpanFull(),
                                        Group::make(fn ($state) => collect([
                                            Form::generateField([...$state, 'label' => 'ברירת מחדל'])
                                                ?->required(false),
                                        ])->filter()->toArray())
                                            ->visible(fn ($state) => ! empty($state['type']) && ! empty($state['label']))
                                            ->columnSpanFull(),
                                        Group::make([
                                            Repeater::make('options')
                                                ->label('אפשרויות')
                                                ->columnSpanFull()
                                                ->addActionLabel('הוסף אפשרות')
                                                ->simple(TextInput::make('name')
                                                    ->label('שם')
                                                    ->required()
                                                ),
                                        ])
                                            ->visible(fn ($state) => in_array($state['type'] ?? null, ['select', 'radio', 'checkbox']))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('edit_pleases')
                            ->label('מיקומי עריכה/יצירה')
                            ->schema([
                                Group::make([
                                    Repeater::make('edit_pleases')
                                        ->label('עריכה')
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->addActionLabel('הוסף פעולה')
                                        ->maxItems(3)
                                        ->schema([
                                            Grid::make(5)
                                                ->schema([
                                                    ToggleButtons::make('place')
                                                        ->options([
                                                            'list' => 'פעולת רשימה',
                                                            'view' => 'פעולת צפייה',
                                                            'edit' => 'אזור בעריכה',
                                                        ])
                                                        ->extraAttributes(['class' => 'divide-x rtl:divide-x-reverse'])
                                                        ->icons([
                                                            'list' => 'iconsax-bul-row-vertical',
                                                            'view' => 'iconsax-bol-eye',
                                                            'edit' => 'iconsax-bul-edit-2',
                                                        ])
                                                        ->grouped()
                                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                        ->label('מקום')
                                                        ->columnSpan(4)
                                                        ->required(),
                                                    Toggle::make('is_grouped')
                                                        ->label('קיבוץ'),
                                                ]),

                                            Grid::make(5)->schema([
                                                TextInput::make('label')
                                                    ->label('תווית')
                                                    ->live()
                                                    ->columnSpan(4)
                                                    ->required(),
                                                Select::make('type_label')
                                                    ->label('סוג תווית')
                                                    ->options([
                                                        'normal' => 'רגיל',
                                                        'tooltip' => 'טקסט עזרה',
                                                    ]),
                                            ]),

                                            Grid::make(5)
                                                ->schema([
                                                    Select::make('icon')
                                                        ->label('אייקון')
//                                                        ->options(fn (Forms\Get $get) => static::getIcons($get('set')))
                                                        ->getSearchResultsUsing(fn (Get $get, $search) => static::getIcons($get('set'), $search))
                                                        ->allowHtml()
                                                        ->searchable()
                                                        ->columnSpan(4)
                                                        ->optionsLimit(50)
                                                        ->required(),
                                                    Select::make('set')
                                                        ->label('סט')
                                                        ->live()
                                                        ->default('iconsax')
                                                        ->options(collect(array_keys(static::getIcons()))->mapWithKeys(fn ($set) => [$set => $set])->toArray())
                                                        ->native(false),
                                                ]),

                                        ]),
                                ]),
                            ]),

                        Tab::make('view_pleases')
                            ->label('מיקומי צפייה')
                            ->schema([
                                Group::make([
                                    Repeater::make('view_pleases')
                                        ->label('צפייה')
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->addActionLabel('הוסף פעולה')
                                        ->maxItems(3)
                                        ->schema([
                                            Grid::make(5)
                                                ->schema([
                                                    ToggleButtons::make('place')
                                                        ->options([
                                                            'list' => 'פעולת רשימה',
                                                            'view' => 'פעולת צפייה',
                                                            'edit' => 'פעולה בעריכה',
                                                        ])
                                                        ->extraAttributes(['class' => 'divide-x rtl:divide-x-reverse'])
                                                        ->icons([
                                                            'list' => 'iconsax-bul-row-vertical',
                                                            'view' => 'iconsax-bol-eye',
                                                            'edit' => 'iconsax-bul-edit-2',
                                                        ])
                                                        ->grouped()
                                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                        ->label('מקום')
                                                        ->columnSpan(4)
                                                        ->required(),
                                                    Toggle::make('is_grouped')
                                                        ->label('קיבוץ'),
                                                ]),

                                            Grid::make(5)->schema([
                                                TextInput::make('label')
                                                    ->label('תווית')
                                                    ->live()
                                                    ->columnSpan(4)
                                                    ->required(),
                                                Select::make('type_label')
                                                    ->label('סוג תווית')
                                                    ->options([
                                                        'normal' => 'רגיל',
                                                        'tooltip' => 'טקסט עזרה',
                                                    ]),
                                            ]),

                                            Grid::make(5)
                                                ->schema([
                                                    Select::make('icon')
                                                        ->label('אייקון')
//                                                        ->options(fn (Forms\Get $get) => static::getIcons($get('set')))
                                                        ->getSearchResultsUsing(fn (Get $get, $search) => static::getIcons($get('set'), $search))
                                                        ->allowHtml()
                                                        ->searchable()
                                                        ->columnSpan(4)
                                                        ->optionsLimit(50)
                                                        ->required(),
                                                    Select::make('set')
                                                        ->label('סט')
                                                        ->live()
                                                        ->default('iconsax')
                                                        ->options(collect(array_keys(static::getIcons()))->mapWithKeys(fn ($set) => [$set => $set])->toArray())
                                                        ->native(false),
                                                ]),

                                        ]),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('שם')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('resource')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'people' => 'אנשים',
                        'students' => 'תלמידים',
                        'proposals' => 'הצעות',
                    })
                    ->badge()
                    ->label('משאב')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }

    public static function getIcons(?string $set = null, ?string $search = null): array|Collection
    {
        $key = 'sbc-icons-picker';

        if (filled($set)) {
            $key .= '.'.$set;
        }

        if (filled($search)) {
            $key .= '.'.$search;
        }

        return Cache::remember($key, now()->addWeek(), function () use ($search, $set) {
            $icons = static::loadIcons();

            if (filled($set)) {
                $icons = $icons[$set];
            }

            if (filled($search)) {
                $icons = collect($icons)->filter(fn ($icon, $key) => str_contains($key, $search))->toArray();
            }

            return $icons;
        });
    }

    public static function loadIcons(): array
    {
        return Cache::remember('sbc-icons', now()->addWeek(), function () {
            $sets = collect(App::make(IconFactory::class)->all());

            $icons = $sets->mapWithKeys(fn ($set) => [$set['prefix'] => []])->toArray();

            $sets->each(function ($set) use (&$icons) {
                $prefix = $set['prefix'];
                foreach ($set['paths'] as $path) {
                    foreach (File::files($path) as $file) {
                        $filename = $prefix.'-'.$file->getFilenameWithoutExtension();
                        $icons[$prefix][$filename] = Blade::render(
                            <<<'HTML'
                            <div class="flex items-center space-x-2 rtl:space-x-reverse text-current">
                                <span><x-icon :name="$value" class="w-5 h-5" /></span>
                                <span>{{ $value }}</span>
                            </div>
                        HTML,
                            ['value' => $filename]
                        );
                    }
                }
            });

            return $icons;
        });
    }
}
