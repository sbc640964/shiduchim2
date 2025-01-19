<?php

namespace App\Filament\Pages\ReportsPage\Widgets;

use App\Filament\Widgets\FilterReportsTrait;
use App\Models\Person;
use App\Models\Proposal;
use App\Models\Subscriber;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use function Pest\Laravel\get;

class ReportsProposalsTableWidget extends BaseWidget
{
    use FilterReportsTrait;

    #[Reactive]
    public ?int $proposal = null;

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
            ->query($this->query())
            ->recordAction('showProposal')
            ->recordClasses(fn (Proposal $record) =>
                $this->proposal == $record->id ? '!bg-gray-50 !dark:bg-white/5 [&>*:first-child]:!relative [&>*:first-child]:before:!absolute [&>*:first-child]:before:!start-0 [&>*:first-child]:before:!inset-y-0 [&>*:first-child]:before:!w-0.5 [&>*:first-child]:before:!bg-primary-600 [&>*:first-child]:dark:before:!bg-primary-500' : ''
            )
            ->columns([
                Tables\Columns\TextColumn::make('guy.full_name')
                    ->description(fn (Proposal $record) => $record->guy->parents_info)
                    ->hidden(fn () => $person && $person->gender === 'B')
                    ->label('בחור')
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['last_name', 'first_name']),

                Tables\Columns\TextColumn::make('girl.full_name')
                    ->hidden(fn () => $person && $person->gender === 'G')
                    ->label('בחורה')
                    ->description(fn (Proposal $record) => $record->girl->parents_info)
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(['last_name', 'first_name']),
                Tables\Columns\TextColumn::make('diaries_count')
                    ->alignCenter()
                    ->label('תיעודים')
                    ->badge()
                    ->color('gray')
                    ->counts(['diaries' => fn ($query) => $query->whereBetween('created_at', [$dateStart, $dateEnd])]),
                Tables\Columns\TextColumn::make('calls_count')
                    ->alignCenter()
                    ->label('שיחות')
                    ->badge()
                    ->color('gray')
                    ->counts(['diaries as calls_count' => fn ($query) => $query
                        ->whereHas('call')
                        ->whereBetween('created_at', [$dateStart, $dateEnd])])
            ]);
    }

    private function query()
    {
        $person = $this->getFilter('person');
        $matchmaker = $this->getFilter('matchmaker');
        $subscription = $this->getFilter('subscription');
        $dateRange = $this->getFilter('dates_range');

        return Proposal::query()
            ->where('created_by', $matchmaker)
            ->with('girl', 'guy')
            ->where(function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange)
                    ->orWhereBetween('updated_at', $dateRange);
            })
            ->where(function ($query) use ($subscription) {
                if(!$subscription) return;

                $dates = [$subscription->start_date, $subscription->end_date];

                $query->whereBetween('created_at', $dates)
                    ->orWhereBetween('updated_at', $dates);
            })
            ->whereHas('people', fn ($query) => $query->whereIn('id', $person));
    }
}
