<?php

namespace App\Filament\Pages;


use App\Filament\Pages\ReportsPage\Widgets\ProposalInfo;
use App\Filament\Pages\ReportsPage\Widgets\ReportsProposalsTableWidget;
use App\Filament\Pages\ReportsPage\Widgets\StatsReportOverview;
use App\Filament\Resources\ProposalResource\Widgets\DiaryListWidget;
use App\Models\Person;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class Reports extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    #[Url]
    public ?int $proposal = null;

    #[On('updateProposalInReportsPage')]
    public function updateProposal(?int $id): void
    {
        $this->proposal = $id;
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $title = 'דוחות';

    protected static ?int $navigationSort = 200;

    protected static string $routePath = '/reports';

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
                            ->format('Y-m-d')
                            ->disableClear(false)
                            ->icon('heroicon-o-calendar')
                            ->placeholder('בחר תאריכים'),
                        Forms\Components\Select::make('person')
                            ->label('מנוי')
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $this->proposal = null;
                            })
                            ->options(fn (Forms\Get $get) =>
                                Person::query()
                                    ->take(10)
                                    ->where('billing_matchmaker', $get('matchmaker'))
                                    ->get()
                                    ->mapWithKeys(fn (Person $person) => [$person->id => $person->full_name])
                            )
                            ->searchable()
                            ->placeholder('כולם'),
                    ])
                    ->columns(3),
            ]);
    }
}
