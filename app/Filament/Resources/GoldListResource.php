<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Arr;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\GoldListResource\Pages\ListGoldLists;
use Filament\Tables\Columns\Layout\Stack;
use Str;
use App\Filament\Resources\GoldListResource\Pages;
use App\Models\Subscriber;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class GoldListResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string | \BackedEnum | null $navigationIcon = 'iconsax-bul-flag';

    protected static ?string $label = "תלמיד";

    protected static ?string $pluralLabel = "תלמידים";

    public static function getPluralModelLabel(): string
    {
        return static::isManager() ? 'מנויים' : 'תלמידים';
    }

    public static function getLabel(): string
    {
        return static::isManager() ? 'מנוי' : 'תלמיד';
    }

    public static function getNavigationLabel(): string
    {
        return static::isManager() ? 'מנויים' : 'רשימת הזהב שלי';
    }

    public static function isManager()
    {
        return auth()->user()->can('students_subscriptions');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('matchmaker', 'student.lastDiary', 'payer', 'student.father', 'student.mother', 'referrer')
            ->withWorkMonth()
            ->when(!static::isManager(), function (Builder $query) {
                $query
                    ->where('user_id', auth()->user()->id)
                    ->whereStatus('active');
            });
    }


    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('student.full_name')
                        ->label('שם')
                        ->html()
                        ->searchable(['first_name', 'last_name'])
                        ->formatStateUsing(fn(Subscriber $record) => str($record->student->full_name . " <span class='opacity-50 text-xs'>({$record->student->external_code_students})</span>")->toHtmlString())
                        ->description(fn(Subscriber $record) => Arr::join([
                            'ב"ר ',
                            $record->student->father->first_name,
                            $record->student->mother ? ' ו' : '',
                            $record->student->mother?->first_name ?? ''
                        ], '')),
                    ...(static::isManager()
                        ? static::getMangerColumns()
                        : static::getMatchmakerColumns()
                    ),
                ]),
                Panel::make([
                    TextColumn::make('last_name')
                ])->collapsible()
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->visible(static::isManager())
                    ->indicateUsing(function (array $data) {
                        return $data['created_at'] ? 'מתאריך: ' . $data['created_at'] : null;
                    })
                    ->label('תאריך רישום'),
                Filter::make('filter')
                    ->visible(static::isManager())
                    ->schema([
                        ToggleButtons::make('no_select_matchmaker')
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
                        Select::make('matchmaker')
                            ->label('שדכן')
                            ->relationship('matchmaker', 'name')
                            ->preload()
                            ->hidden(fn(Get $get) => $get('no_select_matchmaker'))
                            ->searchable(),

                        Select::make('status')
                            ->label('סטטוס')
                            ->options([
                                'active' => 'פעיל',
                                'hold' => 'מושהה',
                                'pending' => 'ממתין',
                                'married' => 'נשוי',
                                'canceled' => 'בוטל',
                                'completed-active' => 'הושלם (תשלום פעיל)',
                                'completed' => 'הושלם',
                            ])
                            ->searchable(),
                        Select::make('lastTransactionStatus')
                            ->label('סטטוס תשלום')
                            ->visible(static::isManager())
                            ->options([
                                'OK' => 'הצליח',
                                'pending' => 'ממתין',
                                'Error' => 'נכשל',
                                'refunded' => 'הוחזר',
                            ]),
                        Select::make('work_day')
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

                        if ($data['matchmaker'] ?? null) {
                            $indicators[] = 'שדכן: ' . User::find($data['matchmaker'])->name;
                        }

                        if ($data['status'] ?? null) {
                            $indicators[] = 'סטטוס: ' . match ($data['status']) {
                                    'active' => 'פעיל',
                                    'hold' => 'מושהה',
                                    'pending' => 'ממתין',
                                    'married' => 'נשוי',
                                    'canceled' => 'בוטל',
                                    'completed-active' => 'הושלם (תשלום פעיל)',
                                    'completed' => 'הושלם',
                                    default => $data['status'],
                                };
                        }

                        if ($data['work_day'] ?? null) {
                            $indicators[] = 'יום: ' . match ($data['work_day']) {
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

                        if ($data['lastTransactionStatus'] ?? null) {
                            $indicators[] = 'סטטוס תשלום: ' . match ($data['lastTransactionStatus']) {
                                    'OK' => 'הצליח',
                                    'pending' => 'ממתין',
                                    'Error' => 'נכשל',
                                    'refunded' => 'הוחזר',
                                };
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['no_select_matchmaker'] ?? null,
                                fn(Builder $query) => $query->whereNull('user_id'),
                                fn(Builder $query) => $query
                                    ->when($data['matchmaker'] ?? null, fn(Builder $query) => $query
                                        ->where('user_id', $data['matchmaker'])
                                    )
                            )
                            ->when($data['status'] ?? null, fn(Builder $query, $value) => $query->where('status', $value))
                            ->when($data['work_day'] ?? null, function (Builder $query, $value) {
                                $value === 'none'
                                    ? $query->whereNull('work_day')
                                    : $query->where('work_day', $value);
                            })
                            ->when($data['lastTransactionStatus'] ?? null, fn(Builder $query, $value) => $query->whereRelation('lastTransaction', 'status', $value));
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->recordActions([
                Action::make('view')
                    ->iconButton()
                    ->icon('heroicon-o-eye')
                    ->url(fn(Subscriber $record) => static::isManager()
                        ? StudentResource::getUrl('subscription', ['record' => $record->student->id])
                        : StudentResource::getUrl('proposals', ['record' => $record->student->id])
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
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
            'index' => ListGoldLists::route('/'),
        ];
    }

    public static function getMangerColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('תאריך רישום')
                ->date('d/m/Y')
                ->description(fn(Subscriber $record) => 'רישום: ' . $record->created_at->diffForHumans())
                ->sortable(),
            TextColumn::make('work_month')
                ->label('חודש פעילות')
                ->badge()
                ->color(fn(Subscriber $record) => match ($record->balance_payments) {
                    1 => 'danger',
                    2 => 'warning',
                    3, 4 => 'success',
                    default => 'gray',
                })
                ->formatStateUsing(fn(Subscriber $record) => $record->work_month . '/' . $record->payments . ' חודשים')
                ->sortable(['balance_payments', 'payments']),
            Stack::make([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'hold' => 'warning',
                        default => 'gray',
                    })
                    ->grow(false)
                    ->formatStateUsing(fn($state) => match (Str::trim($state)) {
                        'active' => 'פעיל',
                        'hold' => 'מושהה',
                        'pending' => 'ממתין',
                        'married' => 'נשוי',
                        'canceled' => 'בוטל',
                        default => $state,
                    })
                    ->label('סטטוס'),
                ToggleColumn::make('is_published')
                    ->extraAttributes(['class' => 'pt-2 pb-0 scale-75'])
                    ->label('פרסום')
                    ->onIcon('heroicon-o-check')
                    ->alignStart()
                    ->grow(false)
                    ->tooltip(fn($state, Subscriber $record) => Str::trim($record->status) === 'pending' ? ($state ? 'פורסם לשדכנים' : 'לא פורסם') : 'מוקצא לשדכן')
                    ->offIcon('heroicon-o-x-mark')
                    ->sortable()
                    ->getStateUsing(fn(Subscriber $record) => Str::trim($record->status) === 'pending' ? $record->is_published : null)
                    ->disabled(fn(Subscriber $record) => Str::trim($record->status) !== 'pending')
            ]),
            Stack::make([
                TextColumn::make('label_matchmaker')
                    ->state(fn(Subscriber $record) => $record->matchmaker ? 'מוקצא לשדכן:' : 'לא הוקצא')
                    ->color(fn(Subscriber $record) => $record->matchmaker ? 'success' : Color::Gray)
                    ->weight('bold'),
                TextColumn::make('matchmaker.name')
                    ->label('שדכן')
                    ->description(fn(Subscriber $record) => 'יום: ' . match ($record->work_day) {
                            1 => 'ראשון',
                            2 => 'שני',
                            3 => 'שלישי',
                            4 => 'רביעי',
                            5 => 'חמישי',
                            6 => 'שישי',
                            7 => 'מוצ"ש',
                            default => $record->work_day,
                        })
            ]),
            TextColumn::make('amount')
                ->label('סכום')
                ->money('ILS')
                ->formatStateUsing(fn($state) => Number::currency($state, 'ILS') . ' לחודש')
                ->description(fn(Subscriber $record) => $record->balance_payments ? str(Arr::join([
                    "נותרו $record->balance_payments חודשים",
                    '<br/>',
                    '<span class="font-bold">תשלום הבא: ' . ($record->next_payment_date?->format('d/m/Y') ?? '---') . '</span>',
                ], ''))->toHtmlString() : null),

            Stack::make([
                TextColumn::make('label_last_diary')
                    ->description(fn(Subscriber $record) => $record->student->lastDiary ? 'פעילות אחרונה:' : 'לא נרשמה פעילות')
                    ->weight('bold'),
                TextColumn::make('student.lastDiary.created_at')
                    ->sortable()
                    ->description(fn(Subscriber $record) => $record->student->lastDiary ? $record->student->lastDiary->created_at->diffForHumans() : null)
                    ->tooltip(fn(Subscriber $record) => $record->student->lastDiary ? $record->student->lastDiary->label_type . ': ' . $record->student->lastDiary->data['description'] : null)
                    ->label('פעילות אחרונה')
                    ->date('d/m/Y'),
            ]),

            TextColumn::make('billingReferrer.full_name')
                ->description('מפנה')
                ->label('מפנה'),

            TextColumn::make('id')
                ->label('תקופת פרוייקט')
                ->description('תקופת פרוייקט נוכחית')
                ->formatStateUsing(function (Subscriber $record) {
                    $start = $record->start_date;
                    $end = $record->end_date;
                    return $start && $end ? $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y') : null;
                }),

            TextColumn::make('lastTransaction.status')
                ->label('סטטוס תשלום')
                ->description('סטטוס תשלום אחרון')
                ->badge()
                ->color(fn($state) => match ($state) {
                    'OK' => 'success',
                    'Error' => 'danger',
                    'refunded' => 'warning',
                    default => 'gray',
                })
                ->formatStateUsing(fn($state) => match ($state) {
                    'OK' => 'הצליח',
                    'pending' => 'ממתין',
                    'Error' => 'נכשל',
                    'refunded' => 'הוחזר',
                    default => $state,
                }),
        ];
    }

    private static function getMatchmakerColumns(): array
    {
        return [
            TextColumn::make('work_day')
                ->badge()
                ->color(fn($state) => now()->day === $state ? 'success' : 'gray')
                ->formatStateUsing(fn($state) => 'יום: ' . match ($state) {
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
