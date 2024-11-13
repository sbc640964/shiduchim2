<?php

namespace App\Filament\Clusters\Settings\Resources;

use App;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\FormResource\Pages;
use App\Models\Form;
use BladeUI\Icons\Factory as IconFactory;
use Filament\Forms;
use Filament\Forms\Form as FilamentForm;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use File;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static ?string $navigationIcon = 'iconsax-bul-layer';

    protected static ?string $cluster = Settings::class;

    protected static ?string $label = 'טופס';

    protected static ?string $pluralLabel = 'טפסים';

    public static function form(FilamentForm $form): FilamentForm
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('general')
                            ->label('פרטים כלליים')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('שם')
                                    ->required()
                                    ->placeholder('הכנס את שם הטופס'),
                                Forms\Components\Select::make('resource')
                                    ->native(false)
                                    ->options([
                                        'people' => 'אנשים',
                                        'students' => 'תלמידים',
                                        'proposals' => 'הצעות',
                                    ])
                                    ->label('משאב')
                                    ->required(),
                                Forms\Components\Group::make([
                                    Forms\Components\Checkbox::make('is_active')
                                        ->default(true)
                                        ->label('פעיל'),
                                    Forms\Components\Checkbox::make('is_multiple')
                                        ->default(false)
                                        ->label('מרובה רשומות'),
                                ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('fields')
                            ->label('שדות')
                            ->schema([
                                Forms\Components\Repeater::make('fields')
                                    ->hiddenLabel()
                                    ->collapsed()
                                    ->deleteAction(fn (\Filament\Forms\Components\Actions\Action $action) => $action->requiresConfirmation()
                                        ->icon('iconsax-bul-trash')
                                        ->modalHeading('מחיקת שדה')
                                    )
                                    ->addActionLabel('הוסף שדה')
                                    ->collapsible()
                                    ->itemLabel(fn ($state) => $state['label'] ?? null)
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('type')
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
                                        Forms\Components\TextInput::make('label')
                                            ->label('תווית')
                                            ->live()
                                            ->required(),
                                        Forms\Components\TextInput::make('placeholder')
                                            ->label('שומר מקום'),
                                        Forms\Components\Checkbox::make('required')
                                            ->label('חובה'),
                                        Forms\Components\Textarea::make('help')
                                            ->label('טקסט עזרה')
                                            ->columnSpanFull(),
                                        Forms\Components\Group::make(fn ($state) => collect([
                                            Form::generateField([...$state, 'label' => 'ברירת מחדל'])
                                                ?->required(false),
                                        ])->filter()->toArray())
                                            ->visible(fn ($state) => ! empty($state['type']) && ! empty($state['label']))
                                            ->columnSpanFull(),
                                        Forms\Components\Group::make([
                                            Forms\Components\Repeater::make('options')
                                                ->label('אפשרויות')
                                                ->columnSpanFull()
                                                ->addActionLabel('הוסף אפשרות')
                                                ->simple(Forms\Components\TextInput::make('name')
                                                    ->label('שם')
                                                    ->required()
                                                ),
                                        ])
                                            ->visible(fn ($state) => in_array($state['type'] ?? null, ['select', 'radio', 'checkbox']))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('edit_pleases')
                            ->label('מיקומי עריכה/יצירה')
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Repeater::make('edit_pleases')
                                        ->label('עריכה')
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->addActionLabel('הוסף פעולה')
                                        ->maxItems(3)
                                        ->schema([
                                            Forms\Components\Grid::make(5)
                                                ->schema([
                                                    Forms\Components\ToggleButtons::make('place')
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
                                                    Forms\Components\Toggle::make('is_grouped')
                                                        ->label('קיבוץ'),
                                                ]),

                                            Forms\Components\Grid::make(5)->schema([
                                                Forms\Components\TextInput::make('label')
                                                    ->label('תווית')
                                                    ->live()
                                                    ->columnSpan(4)
                                                    ->required(),
                                                Forms\Components\Select::make('type_label')
                                                    ->label('סוג תווית')
                                                    ->options([
                                                        'normal' => 'רגיל',
                                                        'tooltip' => 'טקסט עזרה',
                                                    ]),
                                            ]),

                                            Forms\Components\Grid::make(5)
                                                ->schema([
                                                    Forms\Components\Select::make('icon')
                                                        ->label('אייקון')
//                                                        ->options(fn (Forms\Get $get) => static::getIcons($get('set')))
                                                        ->getSearchResultsUsing(fn (Forms\Get $get, $search) => static::getIcons($get('set'), $search))
                                                        ->allowHtml()
                                                        ->searchable()
                                                        ->columnSpan(4)
                                                        ->optionsLimit(50)
                                                        ->required(),
                                                    Forms\Components\Select::make('set')
                                                        ->label('סט')
                                                        ->live()
                                                        ->default('iconsax')
                                                        ->options(collect(array_keys(static::getIcons()))->mapWithKeys(fn ($set) => [$set => $set])->toArray())
                                                        ->native(false),
                                                ]),

                                        ]),
                                ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('view_pleases')
                            ->label('מיקומי צפייה')
                            ->schema([
                                Forms\Components\Group::make([
                                    Forms\Components\Repeater::make('view_pleases')
                                        ->label('צפייה')
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->addActionLabel('הוסף פעולה')
                                        ->maxItems(3)
                                        ->schema([
                                            Forms\Components\Grid::make(5)
                                                ->schema([
                                                    Forms\Components\ToggleButtons::make('place')
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
                                                    Forms\Components\Toggle::make('is_grouped')
                                                        ->label('קיבוץ'),
                                                ]),

                                            Forms\Components\Grid::make(5)->schema([
                                                Forms\Components\TextInput::make('label')
                                                    ->label('תווית')
                                                    ->live()
                                                    ->columnSpan(4)
                                                    ->required(),
                                                Forms\Components\Select::make('type_label')
                                                    ->label('סוג תווית')
                                                    ->options([
                                                        'normal' => 'רגיל',
                                                        'tooltip' => 'טקסט עזרה',
                                                    ]),
                                            ]),

                                            Forms\Components\Grid::make(5)
                                                ->schema([
                                                    Forms\Components\Select::make('icon')
                                                        ->label('אייקון')
//                                                        ->options(fn (Forms\Get $get) => static::getIcons($get('set')))
                                                        ->getSearchResultsUsing(fn (Forms\Get $get, $search) => static::getIcons($get('set'), $search))
                                                        ->allowHtml()
                                                        ->searchable()
                                                        ->columnSpan(4)
                                                        ->optionsLimit(50)
                                                        ->required(),
                                                    Forms\Components\Select::make('set')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('שם')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resource')
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
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListForms::route('/'),
            'create' => Pages\CreateForm::route('/create'),
            'edit' => Pages\EditForm::route('/{record}/edit'),
        ];
    }

    public static function getIcons(?string $set = null, ?string $search = null): array|\Illuminate\Support\Collection
    {
        $key = 'sbc-icons-picker';

        if (filled($set)) {
            $key .= '.'.$set;
        }

        if (filled($search)) {
            $key .= '.'.$search;
        }

        return \Cache::remember($key, now()->addWeek(), function () use ($search, $set) {
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
        return \Cache::remember('sbc-icons', now()->addWeek(), function () {
            $sets = collect(App::make(IconFactory::class)->all());

            $icons = $sets->mapWithKeys(fn ($set) => [$set['prefix'] => []])->toArray();

            $sets->each(function ($set) use (&$icons) {
                $prefix = $set['prefix'];
                foreach ($set['paths'] as $path) {
                    foreach (File::files($path) as $file) {
                        $filename = $prefix.'-'.$file->getFilenameWithoutExtension();
                        $icons[$prefix][$filename] = \Blade::render(
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
