<?php

namespace App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets;

use Blade;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Widgets\FilterReportsTrait;
use App\Models\Person;
use App\Models\Proposal;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;

class ReportsProposalsTableWidget extends BaseWidget
{
    use FilterReportsTrait;

    #[Reactive]
    public ?int $proposal = null;

    public ?string $activeTab = 'new';

    public function setActiveTab($tab): void
    {
        $this->activeTab = $tab;
        unset($this->query);
    }

    public function updating($property, $value)
    {
        if($property === 'filters') {
            unset($this->query);
        }
    }

    public static function getTabsElement(): string
    {
        $html =
<<<'Blade'
<div>
     <x-filament::tabs x-data="{activeTab: 'new'}">
            <x-filament::tabs.item
                alpine-active="activeTab === 'new'"
                x-on:click="activeTab = 'new'"
                wire:click="setActiveTab('new')"
            >
                הצעות חדשות
            </x-filament::tabs.item>

            <x-filament::tabs.item
                alpine-active="activeTab === 'treated'"
                x-on:click="activeTab = 'treated'"
                wire:click="setActiveTab('treated')"
            >
                הצעות שטופלו
            </x-filament::tabs.item>

            <x-filament::tabs.item
                alpine-active="activeTab === 'open'"
                x-on:click="activeTab = 'open'"
                wire:click="setActiveTab('open')"
            >
                הצעות פתוחות
            </x-filament::tabs.item>
          </x-filament::tabs>
    </div>
Blade;

        return Blade::render($html);

    }

    protected function paginateTableQuery(Builder $query): Paginator | CursorPaginator
    {
        return $query->paginate(
            perPage: ($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage(),
            pageName: 'proposals_page',
        );
    }

    public function showProposal(Proposal $record)
    {
        $this->dispatch('updateProposalInReportsPage', id: $record->id);
    }

    public function table(Table $table): Table
    {

        [$dateStart, $dateEnd] = $this->getFilter('dates_range');

        $person = $this->getFilter('person', true)
            ? Person::find($this->getFilter('person', true))
            : null;

        return $table
            ->heading($person ? ('הצעות עבור ' . $person->full_name) : 'הצעות')
            ->query(fn () => $this->query)
            ->recordAction('showProposal')
            ->recordClasses(fn (Proposal $record) =>
                $this->proposal == $record->id ? '!bg-gray-50 !dark:bg-white/5 [&>*:first-child]:!relative [&>*:first-child]:before:!absolute [&>*:first-child]:before:!start-0 [&>*:first-child]:before:!inset-y-0 [&>*:first-child]:before:!w-0.5 [&>*:first-child]:before:!bg-primary-600 [&>*:first-child]:dark:before:!bg-primary-500' : ''
            )
            ->columns([
                IconColumn::make('is_open')
                    ->label('פתוחה')
                    ->width(100)
                    ->alignCenter()
                    ->tooltip(fn (Proposal $record) => $record->is_open
                        ? 'נפתחה בתאריך ' . $record->opened_at->format('d/m/Y')
                        : ($record->closed_at
                            ? collect(['נסגרה בתאריך',
                                $record->closed_at->format('d/m/Y'),
                                'לאחר שנפתחה בתאריך',
                                $record->opened_at->format('d/m/Y'),
                                'מסיבה:',
                                $record->reason_closed
                            ])->join(' ')
                            : null
                        )
                    )
                    ->boolean()
                ,
                TextColumn::make('guy.full_name')
                    ->description(fn (Proposal $record) => $record->guy->parents_info)
                    ->hidden(fn () => $person && $person->gender === 'B')
                    ->label('בחור')
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['last_name', 'first_name']),

                TextColumn::make('girl.full_name')
                    ->hidden(fn () => $person && $person->gender === 'G')
                    ->label('בחורה')
                    ->description(fn (Proposal $record) => $record->girl->parents_info)
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['last_name', 'first_name']),
                TextColumn::make('diaries_count')
                    ->alignCenter()
                    ->label('תיעודים')
                    ->badge()
                    ->color('gray')
                    ->counts(['diaries' => fn ($query) => $query->whereBetween('created_at', [$dateStart, $dateEnd])]),
                TextColumn::make('calls_count')
                    ->alignCenter()
                    ->label('שיחות')
                    ->badge()
                    ->color('gray')
                    ->counts(['diaries as calls_count' => fn ($query) => $query
                        ->whereHas('call')
                        ->when($dateStart, fn ($query) => $query->whereBetween('created_at', [$dateStart, $dateEnd]))
                    ])
            ]);
    }

    #[Computed]
    private function query(?string $activeTab = null)
    {
        $activeTab = $activeTab ?? $this->activeTab;

        $person = $this->getFilter('person');
        $matchmaker = $this->getFilter('matchmaker');
        $subscription = $this->getFilter('subscription');
        $dateRange = $this->getFilter('dates_range');

        $column = $activeTab === 'new' ? 'created_at' : 'updated_at';

        return Proposal::query()
            ->where('created_by', $matchmaker)
            ->with('girl', 'guy')
            ->when(
                in_array($activeTab, ['new', 'treated']),
                fn ($q) => $q
                    ->when($dateRange[0] ?? null, fn ($query) => $query->whereBetween($column, $dateRange))
                    ->where(function ($query) use ($subscription, $column) {
                        if(!$subscription) return;

                        $dates = [$subscription->start_date, $subscription->end_date];

                        $query->whereBetween($column, $dates);
                    })
            )
            ->when($activeTab === 'open',
                fn ($query) => $query
                    ->whereHas('activities', fn ($query) => $query
                        ->where('type', 'open')
                        ->when($dateRange[0] ?? null, fn ($query) => $query->whereBetween('created_at', $dateRange))
                    )
            )
            ->whereHas('people', fn ($query) => $query->whereIn('id', $person));
    }
}
