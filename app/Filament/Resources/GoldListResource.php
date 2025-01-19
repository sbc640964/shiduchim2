<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoldListResource\Pages;
use App\Models\Subscriber;
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
    protected static ?string $model = Subscriber::class;

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
            ->with('matchmaker', 'student.lastDiary', 'payer', 'student.father', 'student.mother', 'referrer')
            ->when(!auth()->user()->can('students_subscriptions'), function (Builder $query) {
                $query->where('user_id', auth()->user()->id);
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
                    TextColumn::make('student.full_name')
                        ->label('שם')
                        ->html()
                        ->searchable(['first_name', 'last_name'])
                        ->formatStateUsing(fn (Subscriber $record) => str($record->student->full_name . " <span class='opacity-50 text-xs'>({$record->student->external_code_students})</span>")->toHtmlString())
                        ->description(fn (Subscriber $record) => \Arr::join([
                            'ב"ר ',
                            $record->student->father->first_name,
                            $record->student->mother ? ' ו' : '',
                            $record->student->mother?->first_name ?? ''
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
                        Forms\Components\Select::make('matchmaker')
                            ->label('שדכן')
                            ->relationship('matchmaker', 'name')
                            ->preload()
                            ->hidden(fn (Forms\Get $get) => $get('no_select_matchmaker'))
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('סטטוס')
                            ->options([
                                'active' => 'פעיל',
                                'hold' => 'מושהה',
                                'pending' => 'ממתין',
                            ])
                            ->searchable(),
                        Forms\Components\Select::make('work_day')
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

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['no_select_matchmaker'] ?? null,
                                fn (Builder $query) => $query->whereNull('user_id'),
                                fn (Builder $query) => $query
                                    ->when($data['matchmaker'] ?? null, fn (Builder $query) => $query
                                        ->where('user_id', $data['matchmaker'])
                                    )
                            )
                            ->when($data['status'] ?? null, fn (Builder $query, $value) => $query->where('status', $value))
                            ->when($data['work_day'] ?? null, function (Builder $query, $value) {
                                $value === 'none'
                                    ? $query->whereNull('work_day')
                                    : $query->where('work_day', $value);
                            });
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->iconButton()
                    ->icon('heroicon-o-eye')
                    ->url(fn (Subscriber $record) =>
                        auth()->user()->can('students_subscriptions')
                            ? StudentResource::getUrl('subscription', ['record' => $record->student->id])
                            : StudentResource::getUrl('proposals', ['record' => $record->student->id])
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
                TextColumn::make('status')
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
                        'married' => 'נשוי',
                        default => $state,
                    })
                    ->label('סטטוס'),
                ToggleColumn::make('is_published')
                    ->extraAttributes(['class' => 'pt-2 pb-0 scale-75'])
                    ->label('פרסום')
                    ->onIcon('heroicon-o-check')
                    ->alignStart()
                    ->grow(false)
                    ->tooltip(fn($state, Subscriber $record) => \Str::trim($record->status) === 'pending' ? ($state ? 'פורסם לשדכנים' : 'לא פורסם') : 'מוקצא לשדכן')
                    ->offIcon('heroicon-o-x-mark')
                    ->sortable()
                    ->getStateUsing(fn (Subscriber $record) => \Str::trim($record->status) === 'pending' ? $record->is_published : null)
                    ->disabled(fn (Subscriber $record) => \Str::trim($record->status) !== 'pending')
            ]),
            Tables\Columns\Layout\Stack::make([
                TextColumn::make('label_matchmaker')
                ->state(fn(Subscriber $record) => $record->matchmaker ? 'מוקצא לשדכן:' : 'לא הוקצא')
                ->color(fn(Subscriber $record) => $record->matchmaker ? 'success' : Color::Gray)
                ->weight('bold'),
                TextColumn::make('matchmaker.name')
                    ->label('שדכן')
                    ->description(fn (Subscriber $record) => 'יום: '.match ($record->work_day) {
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
                ->formatStateUsing(fn ($state) => Number::currency($state, 'ILS') . ' לחודש')
                ->description(fn (Subscriber $record) => $record->balance_payments ? str(\Arr::join([
                    "נותרו $record->balance_payments חודשים",
                    '<br/>',
                    '<span class="font-bold">תשלום הבא: '.($record->next_payment_date?->format('d/m/Y') ?? '---') .'</span>',
                ], ''))->toHtmlString() : null),

            Tables\Columns\Layout\Stack::make([
                TextColumn::make('label_last_diary')
                    ->description(fn(Subscriber $record) => $record->student->lastDiary ? 'פעילות אחרונה:' : 'לא נרשמה פעילות')
                    ->weight('bold'),
                TextColumn::make('student.lastDiary.created_at')
                    ->sortable()
                    ->description(fn (Subscriber $record) => $record->student->lastDiary ? $record->student->lastDiary->created_at->diffForHumans() : null)
                    ->tooltip(fn (Subscriber $record) => $record->student->lastDiary ? $record->student->lastDiary->label_type.': '.$record->student->lastDiary->data['description'] : null)
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

                    if(! $record->balance_payments) return 'לא פעיל';

                    $dates = $record->transactions->pluck('created_at')
                        ->push($record->next_payment_date);

                    /** @var Carbon[] $lastSequence */
                    $lastSequence = [];

                    $dates->each(function ($date) use (&$lastSequence) {
                        if(empty($lastSequence) || ($date->month - end($lastSequence)->month) !== 1)
                        $lastSequence = [$date];
                    });

                    $start = ($lastSequence[0] ?? now())->format('m/y');
                    $end = (end($lastSequence) ?? now())->copy()->addMonths($record->balance_payments - 1)->format('m/y');

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
