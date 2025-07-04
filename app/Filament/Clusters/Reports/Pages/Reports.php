<?php

namespace App\Filament\Clusters\Reports\Pages;


use App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets\ProposalInfo;
use App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets\ReportsProposalsTableWidget;
use App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets\StatsReportOverview;
use App\Models\Person;
use App\Models\Subscriber;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class Reports extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    #[Url]
    public ?int $proposal = null;

    #[On('updateProposalInReportsPage')]
    public function updateProposal(?int $id): void
    {
        $this->proposal = $id;
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $title = 'מנויים';

    protected static ?int $navigationSort = 200;

    protected static ?string $cluster = \App\Filament\Clusters\Reports::class;

    protected static string $routePath = '/subscriptions';

    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_reports');
    }

    public function getWidgets(): array
    {
        return array_merge([
            StatsReportOverview::make(),
            ReportsProposalsTableWidget::make([
                'proposal' => $this->proposal
            ])
        ], $this->proposal ? [
            ProposalInfo::make([
                'proposal' => $this->proposal
            ])
        ]: []);
    }


    public function filtersForm(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('matchmaker')
                            ->label('שדכן')
                            ->options(User::get()->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('person', null);
                                $this->proposal = null;
                            })
                            ->placeholder('בחר שדכן'),
                        DateRangePicker::make('dates_range')
                            ->label('תאריכים')
                            ->defaultThisMonth()
                            ->displayFormat('DD/MM/YYYY')
                            ->format('d/m/Y')
                            ->disableClear(false)
                            ->icon('heroicon-o-calendar')
                            ->placeholder('בחר תאריכים'),
                        Forms\Components\Select::make('person')
                            ->label('תלמיד')
                            ->optionsLimit(200)
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if (! $state) {
                                    $set('subscription', null);
                                    return;
                                }
                                $set('subscription', Person::find($state)->lastSubscription->id);
                                $this->proposal = null;
                            })
                            ->options(fn (Forms\Get $get) =>
                                Person::query()
                                    ->whereHas('subscriptions', function (Builder $query) use ($get) {
                                        $dates = collect(explode(' - ', $get('dates_range')))
                                            ->map(fn ($date) => $date ? now()->createFromFormat('d/m/Y', $date) : null)
                                            ->filter()
                                            ->toArray();

                                        $query
                                            ->where('user_id', $get('matchmaker'))
                                            ->when(count($dates), function (Builder $query) use ($dates) {
                                                $query->where(function ($query) use ($dates) {
                                                    $query->where('start_date', '<=', $dates[1])
                                                        ->where('end_date', '>=', $dates[0]);
                                                });
                                            });
                                    })
                                    ->get()
                                    ->mapWithKeys(fn (Person $person) => [$person->id => $person->full_name])
                            )
                            ->searchable()
                            ->placeholder('כולם'),

                        Forms\Components\Select::make('subscription')
                            ->label('מנוי')
                            ->visible(fn (Forms\Get $get) => $get('person'))
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $this->proposal = null;
                            })
                            ->default(function (Forms\Get $get) {
                                return Person::find($get('person'))?->lastSubscription?->id ?? null;
                            })
                            ->options(function (Forms\Get $get) {
                                $dates = collect(explode(' - ', $get('dates_range')))
                                    ->map(fn ($date) => $date ? now()->createFromFormat('d/m/Y', $date) : null)
                                    ->filter()
                                    ->toArray();

                                return Subscriber::query()
                                    ->where('person_id', $get('person'))
                                    ->when(count($dates), function (Builder $query) use ($dates) {
                                        $query->where(function ($query) use ($dates) {
                                            $query->where('start_date', '<=', $dates[1])
                                                ->where('end_date', '>=', $dates[0]);
                                        });
                                    })->get()->mapWithKeys(fn($subscriber) => [$subscriber->id => $subscriber->getToOptionsSelect()]);
                            })
                            ->searchable()
                            ->placeholder('כולם'),
                    ])
                    ->columns(3),
            ]);
    }
}
