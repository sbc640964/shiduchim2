<?php

namespace App\Filament\Resources\StudentResource\Forms;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\View;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Placeholder;
use Blade;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\StudentResource\Pages\AddProposal;
use App\Models\City;
use App\Models\Family;
use App\Models\Person;
use App\Models\School;
use Carbon\Carbon;
use Filament\Forms\Components;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\KeyValueEntry;
use Illuminate\Support\HtmlString;

class CreateForm
{
    public function __invoke(Schema $schema): Schema
    {
        $isModal = $schema->getLivewire()::class === AddProposal::class;

        return $schema->components([
            Grid::make($isModal ? 2 : 3)
                ->schema([
                    Grid::make(1)
                        ->columnSpan(2)
                        ->schema([
                            $this->getPersonalDetails(),
//                            $this->getSchoolDetails(),
                            $this->getParentsDetails(),
                        ]),
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema($this->getStatus()),
                ]),
        ]);
    }

    private function getPersonalDetails()
    {
        return Section::make('פרטים אישיים')
            ->headerActions([
                Action::make('data-raw')
                    ->visible(fn ($record) => $record?->exists)
                    ->label('נתונים גולמיים מיבוא')
                    ->link()
                    ->modalSubmitAction(false)
                    ->action(fn () => null)
                    ->schema(function (Schema $schema, $record) {
                        return $schema
                            ->record($record)
                            ->components([
                                KeyValueEntry::make('data_raw'),
                            ]);
                    }),
            ])
            ->collapsible()
            ->columns(2)
            ->inlineLabel()
            ->schema(fn (?Person $record = null) => [
                Select::make('gender')
                    ->label('מין')
                    ->options(
                        [
                            'B' => 'בן',
                            'G' => 'בת',
                        ]
                    )
                    ->live()
                    ->required(),

                Family::filamentSelect('parents_family_id', $record?->parentsFamily)
                    ->relationship('parentsFamily', 'name')
                    ->getSearchResultsUsing(fn ($search) => Family::searchNames($search)
                        ->orderBy('name')
                        ->with(['people.father', 'people.fatherInLaw'])
                        ->get()
                        ->pluck('option_select', 'id')
                    )
                    ->label('משפחת הורים')
                    ->allowHtml()
                    ->live()
//                    ->getOptionLabelUsing(fn ($value) => $value ? Family::find($value)?->option_select : null)
                    ->afterStateUpdated(function (Set $set, $old, $state) {
                        $family = Family::with(['city', 'people'])->find($state);
                        if($family) {
                            $set('last_name', $family->name);
                            $set('father_id', $family->husband?->id ?? null);
                            $set('mother_id', $family->wife?->id ?? null);

                            $set('family_address', $family->address);
                            $set('family_city_id', $family->city_id);
                            $set('father_first_name', $family->husband?->first_name ?? null);
                            $set('mother_first_name', $family->wife?->first_name ?? null);
                            $set('father_synagogue_id', $family->husband->schools()->first()?->id ?? null);
                        }
                    }),

                //Fields that will be hidden and will be updated by the family selection
                Hidden::make('father_id')->nullable(),
                Hidden::make('mother_id')->nullable(),

                View::make('divider')->columnSpan(2),

                TextInput::make('last_name')
                    ->label('שם משפחה')
                    ->helperText('שם המשפחה מושפע משם משפחת ההורים')
                    ->readOnly()
                    ->string()
                    ->required(),

                TextInput::make('first_name')
                    ->label('שם פרטי')
                    ->string()
                    ->required(),

                View::make('divider')->columnSpan(2),

                DatePicker::make('born_at')
                    ->label('תאריך לידה')
                    ->live()
                    ->helperText(fn (Get $get) => Carbon::parse($get('born_at'))->hebcal()->hebrewDate(withQuotes: true))
                    ->native(false)
                    ->suffixIcon('heroicon-o-calendar-days')
                    ->displayFormat('d/m/Y')
                    ->required(),
            ]);
    }

    private function getFamilyDetails()
    {
    }

    private function getSchoolDetails()
    {
        return Section::make('פרטי מוסדות לימוד')
            ->columns(2)
            ->collapsed()
            ->schema([
                Select::make('school_id')
                    ->relationship('school', 'name', function ($query, Get $get, $state) {
                        $query->where('gender', $get('gender'));
                    })
                    ->label('מוסד נוכחי')
                    ->preload()
                    ->searchable(),

                Grid::make(2)
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('class')
                            ->label('כיתה')
                            ->string(),

                        TextInput::make('class_serial_in_school')
                            ->label("מס' כיתה"),
                    ]),

                View::make('divider')->columnSpan(2),

                Select::make('prev_school_id')
                    ->relationship('prevSchool', 'name', function ($query, Get $get, $state) {
                        $query->where('gender', $get('gender'));
                    })
                    ->label('מוסד קודם')
                    ->preload()
                    ->searchable(),
            ]);
    }

    private function contactDetails()
    {
    }

    public static function updateRelativesFields($prefix, Set $set, $state): void
    {
        $person = Person::find($state);

        if ($person) {
            $set($prefix.'_name', $person->full_name);
        }

        if ($person && $prefix == 'father') {
            $set('last_name', $person->last_name);
            $set('city_id', $person->city_id);
            $set('address', $person->address ?? '');
            $set('parent_father_name', $person->father_name);
            $set('parent_mother_name', $person->father_in_law_name);
            $set('parent_father_id', $person->father_id);
            $set('parent_mother_id', $person->father_in_law_id);
            // $set('father_synagogue_id', $person->synagogue_id);
        }
    }

    private function getParentsDetails()
    {
        return Section::make('פרטי משפחה')
            ->columns(2)
            ->description(str('פרטי ההורים יוצגו על פי המשפחה שנבחרה <b class="text-danger-600">***ויעודכנו במקור***</b>')->toHtmlString())
            ->schema(function (Get $get, ?Person $record = null) {
                $parentFamily = ($record?->parentsFamily ?? tap($get('parent_family_id'), fn ($value) => Person::find($value)));

                return $parentFamily ? [
                    TextInput::make('father_first_name')
                        ->formatStateUsing(fn (Person $record) => $parentFamily?->husband?->first_name)
                        ->label('שם האב'),

                    TextInput::make('mother_first_name')
                        ->formatStateUsing(fn (Person $record) => $parentFamily?->wife?->first_name)
                        ->label('שם האם'),

                    TextInput::make('family_address')
                        ->formatStateUsing(fn (Person $record) => $parentFamily?->address)
                        ->label('כתובת'),

                    Select::make('family_city_id')
                        ->options(City::orderBy('name')->get()->pluck('name', 'id'))
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->formatStateUsing(fn (Person $record) => $parentFamily?->city?->id)
                        ->label('עיר'),

                    Select::make('father_synagogue_id')
                        ->native(false)
                        ->options(School::with('city')
                            ->orderBy('name')
                            ->whereType(10)
                            ->get()
                            ->mapWithKeys(fn (School $school) => [$school->id => $school->name . ' - ' . $school->city?->name])
                        )
//                        ->loadStateFromRelationshipsUsing(fn (Person $record) => $record->parentsFamily?->husband?->schools()->first()->id)
                        ->formatStateUsing(fn (Person $record) => $parentFamily?->husband?->schools()->first()->id ?? null)
//                        ->getSearchResultsUsing(fn ($query, $search) => School::whereType(10)->where('name', 'like', "%$search%")->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->label('בית כנסת'),
                ] : [
                    Placeholder::make('empty')
                        ->hiddenLabel()
                        ->content( new HtmlString(Blade::render(
                            <<<'Blade'
                            <div class="text-center text-gray-400 py-6 flex justify-center flex-col items-center">
                                <div class="text-9xl opacity-35">
                                <x-icon name="iconsax-bul-house" class="w-24 h-24"/>
                                </div>
                                <div class="text-lg text-gray-400 mt-4">בחר משפחה כדי להציג את פרטי ההורים</div>
                            </div>
Blade
                        )))
                        ->columnSpan(2),
                ];
            });
    }

    private function getStatus(): array
    {
        return [
            Section::make('מזהים חיצוניים')
                ->columns(2)
                ->schema([
                    Person::externalCodeColumn(),
                    Person::externalCodeColumn('external_code_students', 'קוד תלמיד חיצוני'),
                ]),

            Section::make('נתונים נוספים')
                ->columns(1)
                ->schema([
                    SpatieTagsInput::make('tags')
                        ->type(Person::studentTagsKey())
                        ->label('תגיות')
                        ->placeholder('הכנס תגיות')
                        ->helperText('הכנס תגיות כדי לסייע בחיפוש ובסינון'),
                    Textarea::make('info')
                        ->label('הערות')
                        ->string(),
                    Textarea::make('info_private.user_'.auth()->id())
                        ->extraAttributes([
                            'class' => 'bg-red-50'
                        ])
                        ->label('הערות פרטיות של '.auth()->user()->name)
                        ->helperText(str('<span class="text-red-600 text-center font-bold bg-red-50 w-full block py-2 px-2 ring-2 ring-red-200 rounded-lg">--- שים לב!!! הערה זו היא הערה פרטית ותוצג רק לך ---</span>')->toHtmlString())
                        ->string(),
                ]),

            Section::make('קבצים')
                ->columns(1)
                ->schema([
                    Repeater::make('files')
                        ->label('קבצים')
                        ->addActionLabel('הוסף קובץ')
                        ->itemLabel(fn (array $state) => $state['name'])
                        ->collapsed()
                        ->relationship('files')
                        ->columnSpan(1)
                        ->defaultItems(0)
                        ->extraItemActions([
                            //                            function (Repeater $component) {
                            //                                return Components\Actions\Action::make('download')
                            //                                    ->icon('heroicon-o-arrow-down-tray')
                            //                                    ->extraAttributes(['download' => true])
                            //                                    ->hidden(fn (array $arguments) => empty($component->getItemState($arguments['item'])['path']))
                            //                                    ->url(fn (array $arguments) => asset('storage/'.$component->getItemState($arguments['item'])['path'], true));
                            //                            },
                        ])
                        ->schema([
                            Flex::make([
                                Group::make([
                                    TextInput::make('name')
                                        ->label('שם')
                                        ->string()
                                        ->required(),
                                    Textarea::make('description')
                                        ->label('תיאור')
                                        ->string(),
                                ]),
                                FileUpload::make('path')
                                    ->directory('students-images')
                                    ->label('קובץ')
                                    ->imageEditor()
                                    ->visibility('private')
                                    ->downloadable()
                                    ->required(),
                            ]),
                        ]),
                ]),
        ];
    }
}
