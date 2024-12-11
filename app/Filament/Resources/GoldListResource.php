<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoldListResource\Pages;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class GoldListResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static ?string $navigationIcon = 'iconsax-bul-flag';

    protected static ?string $label = "תלמיד";

    protected static ?string $pluralLabel = "תלמידים";

    public static function getNavigationLabel(): string
    {
        return auth()->user()->can('students_subscriptions') ? 'מנויים' : 'רשימת הזהב שלי';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('billing_status')
            ->where('billing_status', '!=', 'married')
            ->when(!auth()->user()->can('students_subscriptions'), function (Builder $query) {
                $query->where('billing_matchmaker', auth()->user()->id);
            })
            ->whereNotNull('external_code_students')
            ->leftJoin('family_person', 'people.id', '=', 'family_person.person_id')
            ->leftJoin('families', 'family_person.family_id', '=', 'families.id')
            ->select('people.*')
            ->where(function (Builder $query) {
                $query->whereNull('families.id')
                    ->orWhere('families.status', '!=', 'married');
            });
    }


    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    TextColumn::make('full_name')
                        ->label('שם')
                        ->html()
                        ->searchable(['first_name', 'last_name'])
                        ->formatStateUsing(fn (Person $record) => str($record->full_name . " <span class='opacity-50 text-xs'>($record->external_code_students)</span>")->toHtmlString())
                        ->description(fn (Person $record) => \Arr::join([
                            'ב"ר ',
                            $record->father->first_name,
                            $record->mother ? ' ו' : '',
                            $record->mother?->first_name ?? ''
                        ], '')),
                    ...(auth()->user()->can('students_subscriptions')
                        ? static::getMangerColumns()
                        : static::getMatchmakerColumns()
                    ),
                ]),
                Tables\Columns\Layout\Panel::make([
                    TextColumn::make('last_name')
                ])->collapsible()
            ])
            ->filters([
                Tables\Filters\Filter::make('filter')
                    ->form([
                        Forms\Components\ToggleButtons::make('no_select_matchmaker')
                            ->boolean('לא מוקצה', 'מוקצה')
                            ->icons([
                                0 => 'heroicon-o-check',
                                1 => 'heroicon-o-x-mark',
                            ])
                            ->colors([
                                0 => 'gray',
                                1 => 'gray',
                            ])
                            ->grouped()
                            ->label('לא מוקצה לשדכן'),
                        Forms\Components\Select::make('billing_matchmaker')
                            ->label('שדכן')
                            ->relationship('billingMatchmaker', 'name')
                            ->preload()
                            ->hidden(fn (Forms\Get $get) => $get('no_select_matchmaker'))
                            ->searchable(),
                        Forms\Components\Select::make('billing_status')
                            ->label('סטטוס')
                            ->options([
                                'active' => 'פעיל',
                                'hold' => 'מושהה',
                                'pending' => 'ממתין',
                            ])
                            ->searchable(),
                        Forms\Components\Select::make('billing_matchmaker_day')
                            ->label('יום')
                            ->options([
                                1 => 'ראשון',
                                2 => 'שני',
                                3 => 'שלישי',
                                4 => 'רביעי',
                                5 => 'חמישי',
                                6 => 'שישי',
                                7 => 'מוצ"ש',
                                'none' => 'לא נבחר',
                            ])
                            ->searchable(),
                    ])->columns(5)
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['no_select_matchmaker'] ?? null) {
                            $indicators[] = 'לא מוקצה לשדכן';
                        }

                        if ($data['billing_matchmaker'] ?? null) {
                            $indicators[] = 'שדכן: ' . User::find($data['billing_matchmaker'])->name;
                        }

                        if ($data['billing_status'] ?? null) {
                            $indicators[] = 'סטטוס: ' . match ($data['billing_status']) {
                                'active' => 'פעיל',
                                'hold' => 'מושהה',
                                'pending' => 'ממתין',
                            };
                        }

                        if ($data['billing_matchmaker_day'] ?? null) {
                            $indicators[] = 'יום: ' . match ($data['billing_matchmaker_day']) {
                                '1' => 'ראשון',
                                '2' => 'שני',
                                '3' => 'שלישי',
                                '4' => 'רביעי',
                                '5' => 'חמישי',
                                '6' => 'שישי',
                                '7' => 'מוצ"ש',
                                'none' => 'לא נבחר',
                            };
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['no_select_matchmaker'] ?? null,
                                fn (Builder $query) => $query->whereNull('billing_matchmaker'),
                                fn (Builder $query) => $query
                                    ->when($data['billing_matchmaker'] ?? null, fn (Builder $query) => $query
                                        ->where('billing_matchmaker', $data['billing_matchmaker'])
                                    )
                            )
                            ->when($data['billing_status'] ?? null, fn (Builder $query, $value) => $query->where('billing_status', $value))
                            ->when($data['billing_matchmaker_day'] ?? null, function (Builder $query, $value) {
                                $value === 'none'
                                    ? $query->whereNull('billing_matchmaker_day')
                                    : $query->where('billing_matchmaker_day', $value);
                            });
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->iconButton()
                    ->icon('heroicon-o-eye')
                    ->url(fn (Person $record) =>
                        auth()->user()->can('students_subscriptions')
                            ? StudentResource::getUrl('subscription', ['record' => $record->id])
                            : StudentResource::getUrl('proposals', ['record' => $record->id])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListGoldLists::route('/'),
        ];
    }

    public static function getMangerColumns(): array
    {
        return [
            Tables\Columns\Layout\Stack::make([
                TextColumn::make('billing_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'hold' => 'warning',
                        default => 'gray',
                    })
                    ->grow(false)
                    ->formatStateUsing(fn ($state) => match (\Str::trim($state)) {
                        'active' => 'פעיל',
                        'hold' => 'מושהה',
                        'pending' => 'ממתין',
                        default => $state,
                    })
                    ->label('סטטוס'),
                ToggleColumn::make('billing_published')
                    ->extraAttributes(['class' => 'pt-2 pb-0 scale-75'])
                    ->label('פרסום')
                    ->onIcon('heroicon-o-check')
                    ->alignStart()
                    ->grow(false)
                    ->tooltip(fn($state, Person $record) => \Str::trim($record->billing_status) === 'pending' ? ($state ? 'פורסם לשדכנים' : 'לא פורסם') : 'מוקצא לשדכן')
                    ->offIcon('heroicon-o-x-mark')
                    ->sortable()
                    ->getStateUsing(fn (Person $record) => \Str::trim($record->billing_status) === 'pending' ? $record->billing_published : null)
                    ->disabled(fn (Person $record) => \Str::trim($record->billing_status) !== 'pending')
            ]),
            Tables\Columns\Layout\Stack::make([
                TextColumn::make('label_matchmaker')
                ->state(fn(Person$record) => $record->billingMatchmaker ? 'מוקצא לשדכן:' : 'לא הוקצא')
                ->color(fn(Person $record) => $record->billingMatchmaker ? 'success' : Color::Gray)
                ->weight('bold'),
                TextColumn::make('billingMatchmaker.name')
                    ->label('שדכן')
                    ->description(fn (Person $record) => 'יום: '.match ($record->billing_matchmaker_day) {
                            1 => 'ראשון',
                            2 => 'שני',
                            3 => 'שלישי',
                            4 => 'רביעי',
                            5 => 'חמישי',
                            6 => 'שישי',
                            7 => 'מוצ"ש',
                            default => $record->billing_matchmaker_day,
                        })
            ]),
            TextColumn::make('billing_amount')
                ->label('סכום')
                ->money('ILS')
                ->formatStateUsing(fn ($state) => Number::currency($state, 'ILS') . ' לחודש')
                ->description(fn (Person $record) => $record->billing_balance_times ? str(\Arr::join([
                    "נותרו $record->billing_balance_times חודשים",
                    '<br/>',
                    '<span class="font-bold">תשלום הבא: '.($record->billing_next_date?->format('d/m/Y') ?? '---') .'</span>',
                ], ''))->toHtmlString() : null),

            Tables\Columns\Layout\Stack::make([
                TextColumn::make('label_last_diary')
                    ->description(fn(Person $record) => $record->lastDiary ? 'פעילות אחרונה:' : 'לא נרשמה פעילות')
                    ->weight('bold'),
                TextColumn::make('lastDiary.created_at')
                    ->sortable()
                    ->description(fn (Person $record) => $record->lastDiary ? $record->lastDiary->created_at->diffForHumans() : null)
                    ->tooltip(fn (Person $record) => $record->lastDiary ? $record->lastDiary->label_type.': '.$record->lastDiary->data['description'] : null)
                    ->label('פעילות אחרונה')
                    ->date('d/m/Y'),
            ]),


            TextColumn::make('billingReferrer.full_name')
                ->description('מפנה')
                ->label('מפנה'),

            TextColumn::make('id')
                ->label('תקופת פרוייקט')
                ->description('תקופת פרוייקט נוכחית')
                ->formatStateUsing(function (Person $record) {

                    if(! $record->billing_balance_times) return 'לא פעיל';

                    $dates = $record->subscriptions->pluck('created_at')
                        ->push($record->billing_next_date);

                    /** @var Carbon[] $lastSequence */
                    $lastSequence = [];

                    $dates->each(function ($date) use (&$lastSequence) {
                        if(empty($lastSequence) || ($date->month - end($lastSequence)->month) !== 1)
                        $lastSequence = [$date];
                    });

                    $start = ($lastSequence[0] ?? now())->format('m/y');
                    $end = (end($lastSequence) ?? now())->copy()->addMonths($record->billing_balance_times - 1)->format('m/y');

                    return "$start-$end";
                }),
        ];
    }

    private static function getMatchmakerColumns(): array
    {
        return [
            TextColumn::make('billing_matchmaker_day')
                ->badge()
                ->color(fn ($state) => now()->day === $state ? 'success' : 'gray')
                ->formatStateUsing(fn ($state) => 'יום: '.match ($state) {
                    1 => 'ראשון',
                    2 => 'שני',
                    3 => 'שלישי',
                    4 => 'רביעי',
                    5 => 'חמישי',
                    6 => 'שישי',
                    7 => 'מוצ"ש',
                    default => $state,
                }),
        ];
    }
}
